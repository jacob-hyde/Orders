<?php

namespace JacobHyde\Orders;

use JacobHyde\Orders\App\Services\PaypalPaymentService;
use JacobHyde\Orders\App\Services\StripePaymentService;
use JacobHyde\Orders\Models\Order;
use JacobHyde\Orders\Models\Payment as PaymentModel;
use JacobHyde\Orders\Models\SubscriptionPlan;
use Exception;
use Illuminate\Support\Arr;
use JacobHyde\Orders\Actions\Payment\CreatePayment;
use JacobHyde\Orders\Actions\Payment\CreatePaymentIntent;
use JacobHyde\Orders\Actions\Payment\CreateRecurringPaymentIntent;

class Payment
{
    /**
     * Amount of payment in cents.
     *
     * @var integer
     */
    public int $amount = 0;

    /**
     * Fee of payment in cents.
     *
     * @var integer
     */
    public int $fee = 0;

    /**
     * Payment Meta data.
     *
     * @var array
     */
    public array $meta =  [];

    /**
     * Payment Processor.
     *
     * @var string
     */
    public string $processor = 'stripe';

    /**
     * User for payment.
     *
     * @var \App\Models\User
     */
    public $user;

    /**
     * Seller for payment.
     *
     * @var \App\Models\User
     */
    public $sellerUser;

    /**
     * Subscription payment (AKA Recurring Payment).
     *
     * @var boolean
     */
    public bool $recurring = false;

    /**
     * Subscription plan.
     *
     * @var \JacobHyde\Orders\Models\SubscriptionPlan
     */
    public $subscriptionPlan;

    /**
     * Remember the users card details.
     *
     * @var boolean
     */
    public bool $rememberCard = false;

    /**
     * Paypal Return URL.
     *
     * @var string
     */
    private string $returnUrl;

    /**
     * Paypal Cancel URL.
     *
     * @var string
     */
    private string $cancelUrl;

    /**
     * Create a payment instance.
     *
     * @return PaymentModel
     */
    public function create(): PaymentModel
    {
        if ($this->recurring) {
            $intent = CreateRecurringPaymentIntent::run($this->user, $this->subscriptionPlan);
        } else {
            $intent = CreatePaymentIntent::run($this->processor, $this->amount, $this->fee, $this->rememberCard, $this->returnUrl, $this->cancelUrl, $this->user, $this->sellerUser);
        }
        return CreatePayment::run($intent, $this->user, $this->sellerUser);
    }

    /**
     * Create a payment instance from fee.
     *
     * @return PaymentModel
     */
    public function createFee(): PaymentModel
    {
        $payment = PaymentModel::create([
            'buyer_user_id' => $this->user->id,
            'sellerUser_id' => $this->sellerUser ? $this->sellerUser->id : null,
            'amount' => self::convertDollarsToCents($this->amount),
            'fee' => self::convertDollarsToCents($this->fee),
            'status' => PaymentModel::STATUS_PENDING
        ]);
        $payment->key = md5($payment->id . $this->user->id . $this->amount);
        $payment->save();
        return $payment;
    }

    /**
     * Update a payment.
     * After the payment intent has been completed on the FE.
     *
     * @param PaymentModel $payment
     * @param array $data
     * @return boolean
     */
    public function update(PaymentModel $payment, array $data): bool
    {
        switch ($payment->processor_method_type) {
            case 'stripe':
                $stripe = new StripePaymentService();
                $paymentStatus = $stripe->updatePaymentStatus($payment->processor);
                break;
            case 'paypal':
                $paypal = new PaypalPaymentService();
                $paymentStatus = $paypal->updatePaymentStatus($payment->processor);
                $paypal->updateData($payment->processor, Arr::except($data, ['coupon_id', '_fbc', '_fbp']));
                break;
        }
        $payment->status = $paymentStatus;
        $payment->save();
        if ($payment->status === PaymentModel::STATUS_PAID && config('orders.status_change_callback')) {
            $payment->order->status = Order::STATUS_COMPLETED;
            $payment->order->save();
            $job_class = config('orders.status_change_callback');
            dispatch(new $job_class($payment, false));
            return true;
        }
        return false;
    }

    /**
     * Create a subscription payment.
     *
     * @param PaymentModel $payment
     * @param array $data
     * @return boolean
     */
    public function createSubscription(PaymentModel $payment, array $data): bool
    {
        if (!config('orders.create_or_swap_subscription_callback')) {
            return false;
        }
        $user = $payment->buyer;
        $subscriptionPlan = $payment->order->subscriptionPlan;
        $paymentable = $payment->paymentables ? $payment->paymentables->pluck('paymentable')->first() : null;
        $subscription = call_user_func(config('orders.create_or_swap_subscription_callback') . '::handle',
            $user, $subscriptionPlan, $paymentable, isset($data['payment_method']) ? $data['payment_method'] : null);
        $stripe = new StripePaymentService();
        $paymentStatus = $stripe->updatePaymentStatus($payment->processor);
        $payment->status = $paymentStatus;
        $payment->save();
        $payment->order->subscription_id = $subscription->id;
        $payment->order->save();
        $payment->status = PaymentModel::STATUS_PAID;
        $payment->save();
        $payment->order->status = Order::STATUS_COMPLETED;
        $payment->order->save();
        if ($paymentable && config('orders.status_change_callback')) {
            $job_class = config('orders.status_change_callback');
            dispatch(new $job_class($payment, false));
        }
        return $subscription->stripe_status === 'active' || $subscription->stripe_status === 'trialing';
    }

    /**
     * Refund a payment.
     *
     * @param PaymentModel $payment
     * @param integer|null $amount
     * @return void
     */
    public function refund(PaymentModel $payment, int $amount = null): void
    {
        switch ($payment->processor_method_type) {
            case 'stripe':
                $stripe = new StripePaymentService();
                $stripe->refund($payment->processor, $amount);
                break;
            case 'paypal':
                $paypal = new PaypalPaymentService();
                $paypal->refund($payment->processor, $amount);
                break;
        }
        $payment->status = $amount ? PaymentModel::STATUS_PARTIAL_REFUNDED : PaymentModel::STATUS_REFUNDED;
        $payment->save();
        if ($payment->order) {
            $payment->order->status = $amount ? Order::STATUS_PARTIAL_REFUNDED : Order::STATUS_REFUNDED;
            $payment->order->save();
        }
    }

    /**
     * Cancel a subscription.
     *
     * @param App\Models\User $user
     * @param string $type
     * @param string $plan
     * @return boolean
     */
    public function cancelSubscription($user, string $type, string $plan): bool
    {
        return $user->subscription($type, $plan)->cancel();
    }

    /**
     * Payout a vendor.
     *
     * @param App\Models\User $user
     * @param int $amount
     * @param string $emailSubject
     * @param string $note
     * @return void
     */
    public function payout($user, int $amount, string $emailSubject, string $note): void
    {
        $paypal_service = new PaypalPaymentService($user);
        $paypal_service->payoutWithAmount($amount, $emailSubject, $note);
    }

    /**
     * Set amount in dollars.
     *
     * @param float $amount
     * @return JacobHyde\Orders\Payment
     */
    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Set the fee in dollars.
     *
     * @param float $fee
     * @return JacobHyde\Orders\Payment
     */
    public function setFee(float $fee): self
    {
        $this->fee = $fee;
        return $this;
    }

    /**
     * Set meta data.
     *
     * @param array $meta
     * @return JacobHyde\Orders\Payment
     */
    public function setMeta(array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    /**
     * Set payment processor
     *
     * @param string $processor
     * @return JacobHyde\Orders\Payment
     */
    public function setProcessor(string $processor): self
    {
        if (!in_array($processor, ['stripe', 'paypal'])) {
            throw new Exception("Unkown payment processor: " . $processor);
        }
        $this->processor = $processor;
        return $this;
    }

    /**
     * Set buying user.
     *
     * @param App\Models\User $user
     * @return JacobHyde\Orders\Payment
     */
    public function setUser($user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Set the seller.
     *
     * @param App\Models\User $sellerUser
     * @return JacobHyde\Orders\Payment
     */
    public function setSeller($sellerUser): self
    {
        $this->sellerUser = $sellerUser;
        return $this;
    }

    /**
     * Set Paypal return URL.
     *
     * @param string $return_url
     * @return JacobHyde\Orders\Payment
     */
    public function setReturnURL(string $return_url): self
    {
        $this->returnUrl = $return_url;
        return $this;
    }

    /**
     * Set Paypal Cancel URL.
     *
     * @param string $cancel_url
     * @return JacobHyde\Orders\Payment
     */
    public function setCancelURL(string $cancel_url): self
    {
        $this->cancelUrl = $cancel_url;
        return $this;
    }

    /**
     * Set recurring.
     *
     * @param boolean $recurring
     * @return JacobHyde\Orders\Payment
     */
    public function setRecurring(bool $recurring): self
    {
        $this->recurring = $recurring;
        return $this;
    }

    /**
     * Set remember card.
     *
     * @param boolean $rememberCard
     * @return JacobHyde\Orders\Payment
     */
    public function rememberCard(bool $rememberCard): self
    {
        $this->rememberCard = $rememberCard;
        return $this;
    }

    /**
     * Set subscription plan.
     *
     * @param SubscriptionPlan $subscriptionPlan
     * @return JacobHyde\Orders\Payment
     */
    public function setSubscriptionPlan(SubscriptionPlan $subscriptionPlan): self
    {
        $this->subscriptionPlan = $subscriptionPlan;
        return $this;
    }

    /**
     * Convert Cents to Dollars.
     *
     * @param integer $amount
     * @return string
     */
    public static function convertCentsToDollars(int $amount): string
    {
        return number_format(($amount / 100), 2, '.', '');
    }

    /**
     * Convert dollars to cents.
     *
     * @param float $amount - Amount in dollars
     * @return int - The amount in cents
     */
    public static function convertDollarsToCents(float $amount): int
    {
        return intval($amount * 100);
    }
}
