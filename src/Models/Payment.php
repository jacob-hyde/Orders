<?php

namespace JacobHyde\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{

    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PARTIAL_REFUNDED = 'partial-refunded';
    public const INTENT_STEP_PAID = 'paid';
    public const INTENT_STEP_PAYMENT_ATTEMPTED = 'payment attempted';
    public const INTENT_STEP_DECLINED = 'declined';

    public const PROCESSOR_TYPES = [
        \JacobHyde\Orders\Models\PaymentIntent::class => 'stripe',
        \JacobHyde\Orders\Models\PaypalOrder::class => 'paypal',
    ];

    protected $table = 'payments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'key',
        'processor_id',
        'processor_type',
        'buyer_user_id',
        'seller_user_id',
        'amount',
        'fee',
        'status',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function getRouteKeyName()
    {
        return 'key';
    }

    public function getProcessorMethodTypeAttribute()
    {
        return self::PROCESSOR_TYPES[$this->processor_type];
    }

    public function processor()
    {
        return $this->morphTo();
    }

    public function paymentables()
    {
        return $this->hasMany(Paymentable::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    public function buyer()
    {
        return $this->belongsTo(config('orders.user'), 'buyer_user_id')->withTrashed();
    }

    public static function deletePaymentFromKey(string $key, bool $delete_processor = true, bool $delete_paymentables = false, bool $delete_order = true): void
    {
        $payment = self::where('key', $key)->with(['paymentables', 'processor'])->first();
        if (!$payment) {
            return;
        }
        if ($delete_paymentables) {
            foreach ($payment->paymentables as $paymentable) {
                if ($paymentable->paymentable) {
                    $paymentable->paymentable->forceDelete();
                }
                $paymentable->forceDelete();
            }
        } else {
            foreach ($payment->paymentables as $paymentable) {
                if ($paymentable->paymentable->order_id) {
                    $paymentable->paymentable->order_id = null;
                    $paymentable->paymentable->save();
                }
                $paymentable->forceDelete();
            }
        }
        if ($delete_processor && $payment->processor) {
            $payment->processor->forceDelete();
        }
        if ($delete_order && $payment->order) {
            if ($payment->order->orderables) {
                foreach ($payment->order->orderables as $orderable) {
                    $orderable->forceDelete();
                }
            }
            $payment->order->forceDelete();
        }
        $payment->forceDelete();
    }

}