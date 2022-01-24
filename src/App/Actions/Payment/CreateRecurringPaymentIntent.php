<?php

namespace JacobHyde\Orders\Actions\Payment;

use JacobHyde\Orders\App\Services\PaypalPaymentService;
use JacobHyde\Orders\App\Services\StripePaymentService;
use JacobHyde\Orders\Models\SubscriptionPlan;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateRecurringPaymentIntent
{
    use AsAction;

    /**
     * Stripe Payment Service
     *
     * @var StripePaymentService
     */
    private StripePaymentService $stripePaymentService;

    /**
     * Construct the CreatePaymentIntent action
     *
     * @param StripePaymentService $stripePaymentService
     * @param PaypalPaymentService $paypalPaymentService
     */
    public function __construct(StripePaymentService $stripePaymentService)
    {
        $this->stripePaymentService = $stripePaymentService;
    }

    /**
     * Handle the action
     *
     * @return void
     */
    public function handle($user, SubscriptionPlan $subscriptionPlan)
    {
        return $this->stripePaymentService
            ->setUser($user)
            ->createSetupIntent($subscriptionPlan);
    }
}
