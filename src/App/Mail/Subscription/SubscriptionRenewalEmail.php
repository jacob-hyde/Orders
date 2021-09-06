<?php

namespace JacobHyde\Orders\App\Mail\Subscription;

use JacobHyde\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SubscriptionRenewalEmail extends Mailable
{
    use Queueable, SerializesModels;

    private $_order;
    private $_buyer;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Order $order, $buyer)
    {
        $this->_order = $order;
        $this->_buyer = $buyer;
        $this->subject('Artist Republik Subscription Renewal');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('orders::mail.subscription.subscription-renewal')
            ->with([
                'order' => $this->_order,
                'buyer' => $this->_buyer,
            ]);
    }
}
