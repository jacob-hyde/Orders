<?php

namespace KnotAShell\Orders;

use KnotAShell\Orders\App\Services\PaypalPaymentService;
use KnotAShell\Orders\App\Services\StripePaymentService;
use KnotAShell\Orders\Models\Order;
use KnotAShell\Orders\Models\Payment as PaymentModel;
use KnotAShell\Orders\Models\SubscriptionPlan;
use Exception;
use Illuminate\Support\Arr;


class Payment
{
    public $amount = 0;
    public $fee = 0;
    public $meta =  [];
    public $processor = 'stripe';
    public $user;
    public $seller_user;
    public $recurring = false;
    public $subscription_plan;
    public $remember_card = false;

    private $_return_url;
    private $_cancel_url;

    public function create(): PaymentModel
    {
        if ($this->recurring) {
            $stripe = new StripePaymentService($this->user);
            $intent = $stripe->createSetupIntent($this->subscription_plan);
        } else if ($this->processor === 'stripe') {
            $stripe = new StripePaymentService($this->user, $this->seller_user);
            $intent = $stripe->create($this->amount, $this->fee, $this->remember_card);
        } else if ($this->processor === 'paypal') {
            $paypal = new PaypalPaymentService($this->user, $this->seller_user);
            $intent = $paypal->create($this->amount, $this->fee, $this->_return_url, $this->_cancel_url);
        }
        $payment = PaymentModel::create([
            'processor_id' => $intent->id,
            'processor_type' => $intent->getMorphClass(),
            'buyer_user_id' => $this->user->id,
            'seller_user_id' => $this->seller_user ? $this->seller_user->id : null,
            'amount' => $this->amount ? self::convertDollarsToCents($this->amount) : $intent->amount,
            'fee' => $this->fee ? self::convertDollarsToCents($this->fee) : null,
            'status' => PaymentModel::STATUS_PENDING
        ]);
        $payment->key = md5($payment->id . $intent->id);
        $payment->save();
        return $payment;
    }

    public function createFee(): PaymentModel
    {
        $payment = PaymentModel::create([
            'buyer_user_id' => $this->user->id,
            'seller_user_id' => $this->seller_user ? $this->seller_user->id : null,
            'amount' => self::convertDollarsToCents($this->amount),
            'fee' => self::convertDollarsToCents($this->fee),
            'status' => PaymentModel::STATUS_PENDING
        ]);
        $payment->key = md5($payment->id . $this->user->id . $this->amount);
        $payment->save();
        return $payment;
    }

    public function update(PaymentModel $payment, array $data): bool
    {
        switch ($payment->processor_method_type) {
            case 'stripe':
                $stripe = new StripePaymentService();
                $payment_status = $stripe->updatePaymentStatus($payment->processor);
                break;
            case 'paypal':
                $paypal = new PaypalPaymentService();
                $payment_status = $paypal->updatePaymentStatus($payment->processor);
                $paypal->updateData($payment->processor, Arr::except($data, ['coupon_id', '_fbc', '_fbp']));
                break;
        }
        $payment->status = $payment_status;
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

    public function createSubscription(PaymentModel $payment, array $data): bool
    {
        if (!config('orders.create_or_swap_subscription_callback')) {
            return false;
        }
        $user = $payment->buyer;
        $subscription_plan = $payment->order->subscription_plan;
        $paymentable = $payment->paymentables ? $payment->paymentables->pluck('paymentable')->first() : null;
        $subscription = call_user_func(config('orders.create_or_swap_subscription_callback') . '::handle',
            $user, $subscription_plan, $paymentable, isset($data['payment_method']) ? $data['payment_method'] : null);
        $stripe = new StripePaymentService();
        $payment_status = $stripe->updatePaymentStatus($payment->processor);
        $payment->status = $payment_status;
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

    public function cancelSubscription($user, $type, $plan)
    {
        return $user->subscription($type, $plan)->cancel();
    }

    public function payout($user, $amount, $email_subject, $note): void
    {
        $paypal_service = new PaypalPaymentService($user);
        $paypal_service->payoutWithAmount($amount, $email_subject, $note);
    }

    public function setAmount(float $amount)
    {
        $this->amount = $amount;
        return $this;
    }

    public function setFee(float $fee)
    {
        $this->fee = $fee;
        return $this;
    }

    public function setMeta(array $meta)
    {
        $this->meta = $meta;
        return $this;
    }

    public function setProcessor(string $processor)
    {
        if (!in_array($processor, ['stripe', 'paypal'])) {
            throw new Exception("Unkown payment processor: " . $processor);
        }
        $this->processor = $processor;
        return $this;
    }

    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    public function setSeller($seller_user)
    {
        $this->seller_user = $seller_user;
        return $this;
    }

    public function setReturnURL(string $return_url)
    {
        $this->_return_url = $return_url;
        return $this;
    }

    public function setCancelURL(string $cancel_url)
    {
        $this->_cancel_url = $cancel_url;
        return $this;
    }

    public function setRecurring(bool $recurring)
    {
        $this->recurring = $recurring;
        return $this;
    }

    public function rememberCard(bool $remember_card)
    {
        $this->remember_card = $remember_card;
        return $this;
    }

    public function setSubscriptionPlan(SubscriptionPlan $subscription_plan)
    {
        $this->subscription_plan = $subscription_plan;
        return $this;
    }

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
