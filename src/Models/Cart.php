<?php

namespace JacobHyde\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Faker\Provider\Uuid;


class Cart extends Model
{
    use SoftDeletes;

    protected $table = 'carts';

    public static function boot()
    {
        parent::boot();
        static::creating(function (self $cart) {
            $cart->uuid = Uuid::uuid();
        });
    }

    protected $fillable = [
        'uuid',
        'api_client_id',
        'order_id',
        'user_id',
        'cartable_type',
        'cartable_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'json',
    ];

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function cartable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(config('arorders.user'));
    }

    public static function createCart(int $api_client_id, $cartable, $user = null): self
    {
        return self::create([
            'api_client_id' => $api_client_id,
            'user_id' => $user ? $user->id : null,
            'cartable_type' => $cartable->getMorphClass(),
            'cartable_id' => $cartable->id,
        ]);
    }
}
