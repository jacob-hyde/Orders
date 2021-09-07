<?php

namespace KnotAShell\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use SoftDeletes;

    protected $table = 'subscription_plans';

    protected $fillable = [
        'id',
        'name',
        'type',
        'product_type_id',
        'stripe_plan',
        'price',
        'description',
        'planable_id',
        'planable_type',
    ];

    public function planable()
    {
        return $this->morphTo();
    }

    public function product_type()
    {
        return $this->belongsTo(ProductType::class);
    }

}
