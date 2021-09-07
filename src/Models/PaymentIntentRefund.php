<?php

namespace KnotAShell\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentIntentRefund extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payment_intent_id',
        'refund_id',
        'reason',
        'status',
        'amount',
    ];
}
