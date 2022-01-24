<?php

namespace JacobHyde\Orders\Actions\Payment;

use JacobHyde\Orders\Models\Contracts\PaymentIntentContract;
use JacobHyde\Orders\Models\Payment;
use Lorisleiva\Actions\Concerns\AsAction;

class CreatePayment
{
    use AsAction;

    public function handle(PaymentIntentContract $intent, $user, $sellerUser): Payment
    {
        $payment = Payment::create([
            'processor_id' => $intent->id,
            'processor_type' => $intent->getMorphClass(),
            'buyer_user_id' => $user->id,
            'sellerUser_id' => $sellerUser ? $sellerUser->id : null,
            'amount' => $intent->amount,
            'fee' => $intent->fee,
            'status' => Payment::STATUS_PENDING
        ]);
        $payment->key = md5($payment->id . $intent->id);
        $payment->save();
        return $payment;
    }
}