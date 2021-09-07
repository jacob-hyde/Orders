<?php

namespace KnotAShell\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $table = 'cart_items';

    protected $fillable = [
        'cart_id',
        'cart_itemable_type',
        'cart_itemable_id',
        'type',
        'meta',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function cart_itemable()
    {
        return $this->morphTo();
    }
}
