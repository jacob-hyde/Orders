<?php

namespace JacobHyde\Orders\Models;

use JacobHyde\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Customer.
 *
 * @property int $id
 * @property int $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property string $address
 * @property string $secondary_address
 * @property string $city
 * @property string $province
 * @property string $zip
 * @property string $country
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property User $user
 * @property Order[] $orders
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Query\Builder
 */
class Customer extends Model
{
    use SoftDeletes;

    /**
     * The table in database associated with the model.
     * @var string
     */
    protected $table = 'customers';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'secondary_address',
        'city',
        'province',
        'zip',
        'country',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function getFullAddressAttribute()
    {
        return $this->address . ', ' . ($this->secondary_address ? $this->secondary_address . ', ' : '') . $this->city . ', ' . $this->province . ' ' . $this->country . ' ' . $this->zip;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(config('orders.user'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Create a customer from user.
     *
     * @param User $user
     * @return Customer
     */
    public static function customerFromUser(
        $user,
        string $first_name = null,
        string $last_name = null,
        string $address = null,
        string $city = null,
        string $state = null,
        string $country = null,
        string $zip = null
    ): self
    {
        if ($customer = $user->customer) {
            if ($first_name || $last_name || $address || $city || $state || $country || $zip) {
                $customer->first_name = $first_name;
                $customer->last_name = $last_name;
                $customer->address = $address;
                $customer->city = $city;
                $customer->province = $state;
                $customer->country = $country;
                $customer->zip = $zip;
                $customer->save();
            }
            return $customer;
        }

        return self::create([
            'user_id' => $user->id,
            'first_name' => $first_name ? $first_name : $user->fname,
            'last_name' => $last_name ? $last_name : $user->lname,
            'email' => $user->email,
            'address' => $address,
            'city' => $city,
            'provence' => $state,
            'country' => $country,
            'zip' => $zip
        ]);
    }

    /**
     * Create a customer from data.
     *
     * @param array $customer_data
     * @return Customer
     */
    public static function customerFromData(array $customer_data): self
    {
        if (isset($customer_data['email'])) {
            return self::updateOrCreate(['first_name' => $customer_data['first_name'], 'last_name' => $customer_data['last_name'], 'email' => $customer_data['email']], $customer_data);
        }

        return self::create($customer_data);
    }
}
