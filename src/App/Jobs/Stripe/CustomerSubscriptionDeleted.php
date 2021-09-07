<?php

namespace KnotAShell\Orders\App\Jobs\Stripe;

use KnotAShell\Orders\App\Mail\Subscription\CustomerSubscriptionDeletedEmail;
use KnotAShell\Orders\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Cashier;
use Spatie\WebhookClient\Models\WebhookCall;

class CustomerSubscriptionDeleted implements ShouldQueue
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
        $subscription = Subscription::where('stripe_id', $response['data']['object']['id'])->where('user_id', $user->id)->first();
        if (!$subscription) {
            return;
        }
        Mail::to($user->email)->queue(new CustomerSubscriptionDeletedEmail($subscription->subscription_plan, $user));
    }
}
