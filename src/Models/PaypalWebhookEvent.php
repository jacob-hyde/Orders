<?php

namespace KnotAShell\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class PaypalWebhookEvent extends Model
{
    protected $table = 'paypal_webhook_events';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'event_id',
        'event_time',
        'resource_type',
        'event_type',
        'resource_id',
        'summary',
        'event',
    ];

    protected $dates = [
        'event_time',
    ];
}
