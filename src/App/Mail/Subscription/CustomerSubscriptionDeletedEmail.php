<?php

namespace KnotAShell\Orders\App\Mail\Subscription;

use KnotAShell\Orders\Models\SubscriptionPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerSubscriptionDeletedEmail extends Mailable
{
    use Queueable, SerializesModels;

    private $_order;
    private $_subscription_plan;
    private $_buyer;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(SubscriptionPlan $subscription_plan, $buyer)
    {
        $this->_subscription_plan = $subscription_plan;
        $this->_buyer = $buyer;
        $this->subject('Artist Republik Subscription Cancelled');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('orders::mail.subscription.subscription-cancelled')
            ->with([
                'subscription_plan' => $this->_subscription_plan,
                'buyer' => $this->_buyer,
            ]);
    }
}
