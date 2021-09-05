<?php

namespace JacobHyde\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Faker\Provider\Uuid;


class Intent extends Model
{

    protected $table = 'intents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'api_client_id',
        'user_id',
        'order_id',
        'step',
        'steppable_id',
        'steppable_type'
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function (self $intent) {
            $intent->uuid = Uuid::uuid();
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function user()
    {
        return $this->belongsTo(config('arorders.user'));
    }

    public function steppable()
    {
        return $this->morphTo();
    }

    public static function createIntent(int $api_client_id, string $step, $steppable, $user = null): self
    {
        return self::create([
            'api_client_id' => $api_client_id,
            'user_id' => $user ? $user->id : null,
            'step' => $step,
            'steppable_id' => $steppable->id,
            'steppable_type' => $steppable->getMorphClass(),
        ]);
    }
}
