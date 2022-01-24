<?php

namespace JacobHyde\Orders\App\Services;

use App\User;
use JacobHyde\Orders\Payment;
use JacobHyde\Orders\Models\Payment as PaymentModel;
use JacobHyde\Orders\Models\PaypalOrder;
use JacobHyde\Orders\Models\PaypalPayout;
use JacobHyde\Orders\Models\PaypalWebhookEvent;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\App;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Payments\CapturesRefundRequest;
use PaypalPayoutsSDK\Payouts\PayoutsPostRequest;
use Illuminate\Support\Facades\Log;
use JacobHyde\Orders\Models\Contracts\PaymentIntentContract;

class PaypalPaymentService extends PaymentService
{

    const PAYPAL_FEE_PERCENTAGE = .029;
    const PAYPAL_ADDITIONAL_FEE = .30;
    
    /**
     * Return Url
     *
     * @var string
     */
    private string $returnUrl;
    
    /**
     * Cancel URL
     *
     * @var string
     */
    private string $cancelUrl;

    /**
     * Seller Merchand Id
     *
     * @var string
     */
    private string $sellerMerchantId;
    
    /**
     * Paypal Client
     *
     * @var PayPalHttpClient
     */
    private PayPalHttpClient $paypalClient;

    /**
     * Sets the user and paypal client.
     *
     */
    public function __construct()
    {
        if (App::environment() !== 'production') {
            $environment = new SandboxEnvironment(config('orders.paypal_client'), config('orders.paypal_secret'));
        } else {
            $environment = new ProductionEnvironment(config('orders.paypal_client'), config('orders.paypal_secret'));
        }
        $this->paypalClient = new PayPalHttpClient($environment);
    }

    /**
     * Create a paypal payment order.
     *
     * @return PaypalOrder
     */
    public function create(): PaymentIntentContract
    {
        if ($this->sellerUser) {
            $this->sellerHasPaypalSetup($this->sellerUser);
            $this->sellerMechantId = $this->sellerUser->{config('orders.seller_paypal_id_column')};
        }

        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = $this->createIntentData();
        $response = $this->paypalClient->execute($request);

        return PaypalOrder::create([
            'buyer_user_id' => $this->_user->id,
            'seller_user_id' => $this->_seller_user ? $this->_seller_user->id : null,
            'order_id' => $response->result->id,
            'status' => $response->result->status,
            'amount' => Payment::convertDollarsToCents($this->amount),
            'fee' => $fee ? Payment::convertDollarsToCents($this->fee) : 0,
            'payment_link' => array_values(array_filter($response->result->links, function ($val) {
                return $val->rel === 'approve';
            }))[0]->href, //TODO seperate this line out
        ]);
    }

    /**
     * Updates the status oof the paypal order.
     *
     * @param PaypalOrder $paypalOrder
     * @return string [paid, declined]
     */
    public function updatePaymentStatus(PaypalOrder $paypalOrder): string
    {
        $request = new OrdersCaptureRequest($paypalOrder->order_id);

        try {
            $response = $this->paypalClient->execute($request);
            $status = $response->result->purchase_units[0]->payments->captures[0]->status;
            $paypalOrder->status = $status;
            $paypalOrder->capture_id = $response->result->purchase_units[0]->payments->captures[0]->id;
            $paypalOrder->save();
            return $status === 'COMPLETED' ? PaymentModel::STATUS_PAID : PaymentModel::STATUS_DECLINED;
        } catch (Exception $e) {
            Log::error($e->message());
            return PaymentModel::STATUS_DECLINED;
        }

    }

    /**
     * Updates the paypal payment data.
     *
     * @param PaypalOrder $paypalOrder
     * @param array $data
     * @return void
     */
    public function updateData(PaypalOrder $paypalOrder, array $data): void
    {
        foreach ($data as $column => $value) {
            $paypalOrder->$column = $value;
        }
        $paypalOrder->save();
    }

    public function refund(PaypalOrder $paypalOrder, int $amount = null): void
    {
        $request = new CapturesRefundRequest($paypalOrder->capture_id);
        $request->body = [
            'amount' => [
                'value' => $amount ? Payment::convertCentsToDollars($amount) : Payment::convertCentsToDollars($paypalOrder->amount),
                'currency_code' => 'USD',
            ],
        ];
        $response = $this->paypalClient->execute($request);
        $paypalOrder->refund_id = $response->result->id;
        $paypalOrder->save();
    }

    /**
     * Payout to user.
     *
     * @param float $amount - The payout amount
     * @param string $emailSubject - The email subject
     * @param string $note - The note to go with the payout
     * @return PaypalPayout
     */
    public function payoutWithAmount(float $amount, string $emailSubject, string $note)
    {
        $request = new PayoutsPostRequest();
        $amount = round($amount - (($amount * self::PAYPAL_FEE_PERCENTAGE) + self::PAYPAL_ADDITIONAL_FEE), 2);
        $request->body = $this->createPayoutData($amount, $this->_user, $emailSubject, $note);
        try {
            $response = $this->paypalClient->execute($request);
            return PaypalPayout::create([
                'user_id' => $this->_user->id,
                'paypal_email' => $this->_user->paypal_email,
                'amount' => Payment::convertDollarsToCents($amount),
                'payout_batch_id' => $response->result->batch_header->payout_batch_id,
                'status' => $response->result->batch_header->batch_status,
                'email_subject' => $emailSubject,
                'note' => $note,
            ]);
        } catch (Exception $e) {
            Log::error("Paypal Error Message: {$e->getMessage()} \n");
            return false;
        }
    }

    /**
     * Set return URL
     *
     * @param string $returnUrl
     * @return self
     */
    public function setReturnUrl(string $returnUrl): self
    {
        $this->_returnUrl = $returnUrl;
        return $this;
    }

    /**
     * Set cancel URL
     *
     * @param string $cancelUrl
     * @return self
     */
    public function setCancelUrl(string $cancelUrl): self
    {
        $this->_cancelUrl = $cancelUrl;
        return $this;
    }

    /**
     * Checks if a given seller (user) has paypal set up.
     *
     * @param User $seller - The seller's User model
     * @return bool
     */
    private function sellerHasPaypalSetup($seller): bool
    {
        if (!$seller->{config('orders.seller_paypal_id_column')}) {
            $error_msg = strtr('Seller with id: {id} does not have paypal setup', ['{id}' => $seller->id]);
            throw new Exception($error_msg);
        }

        return true;
    }

    /**
     * Creaetes the paypal order data.
     *
     * @return array
     */
    protected function createIntentData(): array
    {
        $purchase_unit = [
            'amount' => [
                'value' => $this->amount,
                'currency_code' => 'USD',
            ],
        ];
        if ($this->sellerMerchantId) {
            $purchase_unit['payee'] = ['merchant_id' => $this->sellerMerchantId];
            $purchase_unit['payment_instruction'] = [
                'platform_fees' => [
                    [
                        'amount' => [
                            'value' => $this->fee,
                            'currency_code' => 'USD',
                        ],
                    ],
                ],
            ];
        }

        return [
            'intent' => 'CAPTURE',
            'payer' => [
                'name' => [
                    'given_name' => $this->user->fname,
                    'surname' => $this->user->lname,
                ],
                'email_address' => $this->user->email,
            ],
            'purchase_units' => [
                $purchase_unit,
            ],
            'application_context' => [
                'brand_name' => 'Artist Republik',
                'return_url' => config('app.front_end_url') . $this->returnUrl,
                'cancel_url' => config('app.front_end_url') . $this->cancelUrl,
            ],
        ];
    }

    private function createPayoutData(float $amount, $reciever, string $emailSubject, string $note)
    {
        return [
            'sender_batch_header' => [
                'email_subject' => $emailSubject,
            ],
            'items' => [
                [
                    'recipient_type' => 'EMAIL',
                    'receiver' => $reciever->paypal_email,
                    'note' => $note,
                    'amount' => [
                        'currency' => 'USD',
                        'value' => $amount,
                    ],
                ],
            ],
        ];
    }

    public static function handleWebhookEvent(array $data): void
    {
        $paypalWebhookEvent = PaypalWebhookEvent::create([
            'event_id' => $data['id'],
            'event_time' => Carbon::parse($data['create_time']),
            'resource_type' => $data['resource_type'],
            'event_type' => $data['event_type'],
            'summary' => $data['summary'],
            'event' => json_encode($data),
        ]);
        switch ($data['resource_type']) {
            case 'payouts_item':
                try {
                    $payout = PaypalPayout::where('payout_batch_id', $data['resource']['payout_batch_id'])->first();
                    $payout->status = $data['resource']['transaction_status'];
                    $payout->save();
                    $paypalWebhookEvent->resource_id = $data['resource']['payout_batch_id'];
                    $paypalWebhookEvent->save();
                } catch (Exception $e) {
                    Log::error("Payout Status Update Failed: {$e->getMessage()} \n");
                    return;
                }
            break;
            default:
                return;
        }
    }
}
