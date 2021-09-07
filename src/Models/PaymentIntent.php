<?php

namespace KnotAShell\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentIntent extends Model
{

    use SoftDeletes;
    
    protected $table = 'payment_intents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'buyer_id',
        'seller_id',
        'intent_id',
        'client_secret',
        'amount',
        'fee',
        'customer',
        'meta',
        'status',
        'originating_transaction_id',
        'seller_stripe_id',
        'application_fee_stripe_id',
        'application_fee_id',
        'on_behalf_of',
        'transfer_data_destination',
        'transfer_id',
        'transfer_status',
        'application_fee_amount'
    ];

    public function getPaymentIdentifierAttribute()
    {
        return $this->client_secret;
    }

    public function seller()
    {
        return $this->belongsTo(config('orders.user'), 'buyer_id', 'id');
    }

    public function buyer()
    {
        return $this->belongsTo(config('aroders.user'), 'seller_id', 'id');
    }

}
