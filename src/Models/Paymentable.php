<?php

namespace KnotAShell\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paymentable extends Model
{
    use SoftDeletes;

    protected $table = 'paymentables';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'payment_id',
        'paymentable_id',
        'paymentable_type',
    ];

    public function paymentable()
    {
        return $this->morphTo('paymentable');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
