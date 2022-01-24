<?php

namespace JacobHyde\Orders\App\Services;

use JacobHyde\Orders\Payment;
use JacobHyde\Orders\Models\Payment as PaymentModel;
use JacobHyde\Orders\Models\PaymentIntent;
use JacobHyde\Orders\Models\PaymentIntentRefund;
use JacobHyde\Orders\Models\SubscriptionPlan;
use Exception;
use JacobHyde\Orders\Models\Contracts\PaymentIntentContract;
use Stripe\Customer;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\RateLimitException;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Refund;
use Stripe\SetupIntent;
use Stripe\Stripe;

class StripePaymentService extends PaymentService
{
    /**
     * Remember Card
     *
     * @var boolean
     */
    private bool $rememberCard;

    /**
     * Customer
     *
     * @var Customer
     */
    private Customer $customer;

    /**
     * Sets the user and stripe api key.
     *
     * @param $user - The user which is being charged
     */
    public function __construct()
    {
        Stripe::setApiKey(config('orders.stripe_secret'));
    }

    /**
     * Creates a stripe payment intent as well as a payment intent model.
     *
     * @return PaymentIntent - A payment intent model
     */
    public function create(): PaymentIntentContract
    {

       if ($this->sellerUser) {
           $this->sellerHasStripeSetup($this->sellerUser->seller_stripe_id);
       }

        $this->customer = self::createCustomerFromUser($this->user);
        $this->user = $this->user->fresh();
        $intentData = $this->createIntentData();

        $stripeIntent = $this->createPaymentIntent($intentData);

        return PaymentIntent::create([
            'buyer_id' => $this->_user->id,
            'seller_id' => $this->sellerUser ? $this->sellerUser->id : null,
            'intent_id' => $stripeIntent->id,
            'client_secret' => $stripeIntent->client_secret,
            'amount' => $intentData['amount'],
            'fee' => isset($intentData['application_fee_amount']) ? $intentData['application_fee_amount'] : 0,
            'customer' => $stripeIntent->customer,
            'status' => $stripeIntent->status,
        ]);
    }

    public function createSetupIntent(SubscriptionPlan $subscriptionPlan): PaymentIntent
    {
        $stripe_intent = $this->user->createSetupIntent();

        return PaymentIntent::create([
            'buyer_id' => $this->user->id,
            'seller_id' => null,
            'intent_id' => $stripe_intent->id,
            'client_secret' => $stripe_intent->client_secret,
            'amount' => Payment::convertDollarsToCents($subscriptionPlan->planable->price),
            'fee' => 0,
            'customer' => $stripe_intent->customer,
            'status' => $stripe_intent->status,
        ]);
    }

    /**
     * Takes in a payment intent and charges it.
     *
     * @param PaymentIntent $intent - The payment intent model
     * @param string $paymentId - The payment method id
     * @return string - The status of the charge
     */
    public function chargePaymentIntent(PaymentIntent $intent, string $paymentId): string
    {
        $paymentMethod = PaymentMethod::retrieve($paymentId);
        $paymentMethod->attach([
            'customer' => $this->_user->{config('orders.user_stripe_customer_id_column')},
        ]);
        $paymentIntent = StripePaymentIntent::retrieve($intent->intent_id);
        $paymentIntent = $paymentIntent->confirm([
            'payment_method' => $paymentId,
        ]);
        $intent->status = $paymentIntent->status;
        $intent->save();

        return $paymentIntent->status;
    }

    /**
     * Updates the payment status.
     *
     * @param PaymentIntent $intent
     * @return string [paid, declined]
     */
    public function updatePaymentStatus(PaymentIntent $intent): string
    {
        if (preg_match('/seti_(.*)/', $intent->intent_id)) {
            $stripeIntent = SetupIntent::retrieve($intent->intent_id);
        } else {
            $stripeIntent = StripePaymentIntent::retrieve($intent->intent_id);
        }
        $intent->status = $stripeIntent->status;
        $intent->save();

        return $stripeIntent->status === 'succeeded' ? PaymentModel::STATUS_PAID : PaymentModel::STATUS_DECLINED;
    }

    /**
     * Refund a given payment intent.
     *
     * @param PaymentIntent $intent
     * @return PaymentIntentRefund
     */
    public function refund(PaymentIntent $intent, int $amount = null): PaymentIntentRefund
    {
        $refundData = [
            'payment_intent' => $intent->intent_id,
        ];
        if ($amount) {
            $refundData['amount'] = $amount;
        }
        $refund = Refund::create($refundData);

        return PaymentIntentRefund::create([
            'payment_intent_id' => $intent->id,
            'refund_id' => $refund->id,
            'reason' => $refund->reason ? $refund->reason : '',
            'status' => $refund->status,
            'amount' => $refund->amount,
        ]);
    }

    /**
     * Creates a stripe customer from a user
     * Saves the customer id to user stripe_customer_id field.
     *
     * @param $user - The user
     * @return Customer - A stripe customer instance
     */
    public static function createCustomerFromUser($user): Customer
    {
        Stripe::setApiKey(config('orders.stripe_secret'));

        if ($user->{config('orders.user_stripe_customer_id_column')}) {
            $customer = null;
            try {
                $customer = Customer::retrieve($user->{config('orders.user_stripe_customer_id_column')});
            } catch (Exception $e) {
            }
            if ($customer) {
                return $customer;
            }
        }
        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->fname.' '.$user->lname,
        ]);
        $user->{config('orders.user_stripe_customer_id_column')} = $customer->id;
        $user->save();

        return $customer;
    }

    /**
     * Set remember card
     *
     * @param boolean $rememberCard
     * @return self
     */
    public function setRememberCard(bool $rememberCard): self
    {
        $this->rememberCard = $rememberCard;
        return $this;
    }

    /**
     * Verifies that the payment intent has succeeded.
     *
     * @param PaymentIntent $intent - The payment intent
     * @param bool $updateIntent - If we should update the status of the payment intent
     * @return bool
     */
    public static function verifyPaymentForIntent(PaymentIntent $intent, bool $updateIntent = true): bool
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $stripe_intent = StripePaymentIntent::retrieve($intent->intent_id);
        
        if ($updateIntent) {
            $intent->status = $stripe_intent->status;
            $intent->save();
        }

        return $stripe_intent->status === 'succeeded';
    }

    /**
     * Attach a customer to a payment method.
     *
     * @param string $customerId - The stripe customer id
     * @param string $paymentId - The stripe payment id
     * @return void
     */
    public static function attachCustomerToPaymentMethod(string $customerId, string $paymentId): void
    {
        Stripe::setApiKey(config('orders.stripe_secret'));
        $payment_method = PaymentMethod::retrieve($paymentId);
        $payment_method->attach([
            'customer' => $customerId,
        ]);
    }

    /**
     * Checks if a given seller (user) has stripe set up.
     *
     * @param $seller - The seller's User model
     * @return bool
     */
    private function sellerHasStripeSetup($seller): bool
    {
        if (!$seller->seller_stripe_id) {
            $error_msg = strtr('Seller with id: {id} does not have stripe setup', ['{id}' => $seller->id]);
            throw new Exception($error_msg);
        }

        return true;
    }

    /**
     * Creates the stripe payment intent data.
     *
     * @return array - The information to be passed when creating a stripe payment intent
     */
    protected function createIntentData(): array
    {
        $amount = Payment::convertDollarsToCents($this->amount);
        
        if ($this->fee) {
            $amount += Payment::convertDollarsToCents($this->fee);
        }

        $intentData = [
            'payment_method_types' => ['card'],
            'amount' => $amount,
            'currency' => 'usd',
            'customer' => $this->customer['id'],
        ];

        if ($this->rememberCard) {
            $intentData['setup_future_usage'] = 'off_session';
        }

        if ($this->sellerUser) {
            $sellerFee = $this->sellerUser->fee;
            $sellerFeeAmount = $sellerFee * $amount;
            $apiClientFee = $amount - $sellerFeeAmount;
            $intentData['application_fee_amount'] = $apiClientFee;
            $intentData['on_behalf_of'] = $this->sellerUser->seller_stripe_id;
            $intentData['transfer_data'] = ['destination' => $this->sellerUser->seller_stripe_id];
        }

        if ($this->meta) {
            foreach($this->meta as $key => $value) {
                $intentData["metadata[$key]"] = $value;
            }
        }
        
        return $intentData;
    }

    /**
     * Creates the actual stripe payment intent.
     *
     * @param array $intentData - The data for the stripe payment intent
     * @return StripePaymentIntent - The stripe payment intent
     */
    private function createPaymentIntent(array $intentData): StripePaymentIntent
    {
        $errorMsg = '';

        try {
            $paymentIntent = StripePaymentIntent::create($intentData);
        } catch (CardException | RateLimitException | InvalidRequestException | AuthenticationException | ApiConnectionException | ApiErrorException $e) {
            $errorMsg = strtr('Creating payment intent failed with error code: {error_code} and message: {error_message}',
                                ['{error_code}' => $e->getStripeCode(),
                                '{error_message}' => $e->getMessage(), ]);
        } catch (Exception $e) {
            $errorMsg = strtr('Creating payment intent failed with error code: {error_code} and message: {error_message}',
                                ['{error_code}' => $e->getCode(),
                                '{error_message}' => $e->getMessage(), ]);
        }
        if ($errorMsg !== '') {
            throw new Exception($errorMsg);
        }

        return $paymentIntent;
    }
}
