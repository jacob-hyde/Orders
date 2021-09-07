<?php

namespace KnotAShell\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaypalOrder extends Model
{

    use SoftDeletes;

    protected $table = 'paypal_orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'order_id',
        'capture_id',
        'refund_id',
        'payer_id',
        'buyer_user_id',
        'seller_user_id',
        'product_type_id',
        'status',
        'amount',
        'fee',
        'payment_link',
    ];

    public function getPaymentIdentifierAttribute()
    {
        return $this->order_id;
    }

    public function seller()
    {
        return $this->belongsTo(config('orders.user'), 'seller_user_id');
    }

    public function buyer()
    {
        return $this->belongsTo(config('orders.user'), 'buyer_user_id');
    }

}