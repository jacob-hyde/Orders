<?php

namespace JacobHyde\Orders\Models;

use eloquentFilter\QueryFilter\ModelFilters\Filterable;
use Faker\Provider\Uuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Subscription;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use SoftDeletes;
    use Filterable;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PLACED = 'placed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PARTIAL_REFUNDED = 'partial-refund';

    private static $whiteListFilter = [
        'id',
        'uuid',
        'status',
        'product_type_name_like',
        'amount',
        'buyer',
        'buyer_user_id',
        'buyer_fname_like',
        'buyer_lname_like',
        'buyer_email_like',
        'created_at',
    ];

    /**
     * The table in database associated with the model.
     * @var string
     */
    protected $table = 'orders';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'id',
        'uuid',
        'product_type_id',
        'api_client_id',
        'subscription_plan_id',
        'subscription_id',
        'payment_id',
        'customer_id',
        'buyer_user_id',
        'seller_user_id',
        'status',
        'amount',
        'invoice_url',
        'notes',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function (self $order) {
            $order->uuid = Uuid::uuid();
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function product_type_name_like($builder, $value)
    {
        return $builder->with(['product_type'])->whereHas('product_type', function ($q) use ($value) {
            return $q->where('name', 'like', $value);
        });
    }

    public function buyer_first_name_like($builder, $value)
    {
        return $builder->with(['buyer'])->whereHas('buyer', function ($q) use ($value) {
            return $q->where('first_name', 'like', $value);
        });
    }

    public function buyer_last_name_like($builder, $value)
    {
        return $builder->with(['buyer'])->whereHas('buyer', function ($q) use ($value) {
            return $q->where('last_name', 'like', $value);
        });
    }

    public function buyer_email_like($builder, $value)
    {
        return $builder->with(['buyer'])->whereHas('buyer', function ($q) use ($value) {
            return $q->where('email', 'like', $value);
        });
    }

    public function product_type()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function buyer()
    {
        return $this->belongsTo(config('arorders.user'), 'buyer_user_id');
    }

    public function seller()
    {
        return $this->belongsTo(config('arorders.user'), 'seller_user_id');
    }

    public function orderables()
    {
        return $this->hasMany(Orderable::class);
    }

    public function subscription_plan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function scopeWithPayment($q)
    {
        return $q->leftJoin('payments', 'payments.id', '=', 'orders.payment_id');
    }

    /**
     * Create a new order.
     *
     * @param string $product_type
     * @param Payment $payment
     * @param Customer $customer
     * @param User $buyer_user
     * @param User $seller_user
     * @param string $status
     * @param int $amount
     * @param string $notes
     * @return Order
     */
    public static function createOrder(Payment $payment, Customer $customer, $buyer_user, string $status, int $amount, ProductType $product_type, int $seller_user_id = null, int $api_client_id = null, SubscriptionPlan $subscription_plan = null, string $notes = null): self
    {
        return self::create([
            'api_client_id' => $api_client_id,
            'product_type_id' => $product_type->id,
            'subscription_plan_id' => $subscription_plan->id ?? null,
            'payment_id' => $payment ? $payment->id : null,
            'customer_id' => $customer->id,
            'buyer_user_id' => $buyer_user ? $buyer_user->id : null,
            'seller_user_id' => $seller_user_id,
            'status' => $status,
            'amount' => $amount,
            'notes' => $notes,
        ]);
    }
}
