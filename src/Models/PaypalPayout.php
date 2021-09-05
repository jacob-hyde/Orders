<?php

namespace JacobHyde\Orders\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaypalPayout extends Model
{
    use SoftDeletes;

    protected $table = 'paypal_payouts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'paypal_email',
        'amount',
        'payout_batch_id',
        'status',
        'email_subject',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(config('arorders.user'));
    }
}
