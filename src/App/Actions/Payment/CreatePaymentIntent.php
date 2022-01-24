<?php

namespace JacobHyde\Orders\Actions\Payment;

use JacobHyde\Orders\App\Services\PaypalPaymentService;
use JacobHyde\Orders\App\Services\StripePaymentService;
use Lorisleiva\Actions\Concerns\AsAction;
use PaymentIntentContract;

class CreatePaymentIntent
{
    use AsAction;

    /**
     * Stripe Payment Service
     *
     * @var StripePaymentService
     */
    private StripePaymentService $stripePaymentService;

    /**
     * Paypal Payment Service
     *
     * @var PaypalPaymentService
     */
    private PaypalPaymentService $paypalPaymentService;

    /**
     * Construct the CreatePaymentIntent action
     *
     * @param StripePaymentService $stripePaymentService
     * @param PaypalPaymentService $paypalPaymentService
     */
    public function __construct(StripePaymentService $stripePaymentService, PaypalPaymentService $paypalPaymentService)
    {
        $this->stripePaymentService = $stripePaymentService;
        $this->paypalPaymentService = $paypalPaymentService;
    }

    /**
     * Handle the action
     *
     * @return void
     */
    public function handle(string $processor, float $amount, float $fee, bool $rememberCard, string $returnUrl, string $cancelUrl, $user, $sellerUser): PaymentIntentContract
    {
        $processor = $processor === 'stripe' ? $this->stripePaymentService : $this->paypalPaymentService;
        return $processor
            ->setAmount($amount)
            ->setFee($fee)
            ->setRememberCard($rememberCard)
            ->setReturnUrl($returnUrl)
            ->setCancelUrl($cancelUrl)
            ->setUser($user)
            ->setSeller($sellerUser)
            ->create();
    }
}
