<?php

namespace KnotAShell\Orders\App\Jobs\Stripe;

use KnotAShell\Orders\App\Mail\Subscription\SubscriptionRenewalEmail;
use KnotAShell\Orders\Models\Order;
use KnotAShell\Orders\Models\Payment;
use KnotAShell\Orders\Models\StripeInvoice;
use KnotAShell\Orders\Models\Subscription;
use Faker\Provider\Uuid;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Cashier\Cashier;
use Spatie\WebhookClient\Models\WebhookCall;

class InvoicePaymentSucceeded implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $webhookCall;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(WebhookCall $webhookCall)
    {
        $this->webhookCall = $webhookCall;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $response = $this->webhookCall->payload;
        $user = Cashier::findBillable($response['data']['object']['customer']);
        if (!$user) {
            return;
        }
        $subscription = Subscription::where('stripe_id', $response['data']['object']['subscription'])->where('user_id', $user->id)->first();
        if (!$subscription) {
            return;
        }
        //create the invoice
        $invoice = StripeInvoice::updateOrCreate(['invoice_id' => $response['data']['object']['id']], [
            'user_id' => $user->id,
            'invoice_id' => $response['data']['object']['id'],
            'customer_id' => $response['data']['object']['customer'],
            'subscription_id' => $response['data']['object']['subscription'],
            'amount_due' => $response['data']['object']['amount_due'],
            'amount_paid' => $response['data']['object']['amount_paid'],
            'status' => $response['data']['object']['status'],
            'billing_reason' => $response['data']['object']['billing_reason'],
            'invoice_pdf' => $response['data']['object']['invoice_pdf'],
        ]);
        //create payment
        $payment = Payment::create([
            'processor_id' => $invoice->id,
            'processor_type' => $invoice->getMorphClass(),
            'buyer_user_id' => $user->id,
            'seller_user_id' => null,
            'amount' => $response['data']['object']['amount_due'],
            'fee' => 0,
            'status' => Payment::STATUS_PAID,
        ]);
        $payment->key = md5($subscription->subscription_plan->product_type->id.$payment->id);
        $payment->save();
        $invoice = file_get_contents($response['data']['object']['invoice_pdf']);
        $file_path = $user->id.'/'.Uuid::uuid().'.pdf';
        Storage::disk('s3_invoices')->put($file_path, $invoice);
        //create order
        $order = Order::create([
            'product_type_id' => $subscription->subscription_plan->product_type->id,
            'subscription_plan_id' => $subscription->subscription_plan->id,
            'subscription_id' => $subscription->id,
            'payment_id' => $payment->id,
            'customer_id' => $user->customer->id,
            'buyer_user_id' => $user->id,
            'status' => Order::STATUS_COMPLETED,
            'invoice_url' => $file_path,
            'amount' => $response['data']['object']['amount_due'],
        ]);
        Mail::to($user->email)->queue(new SubscriptionRenewalEmail($order, $user));
    }
}
