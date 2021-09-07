<?php

namespace KnotAShell\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Orderable extends Model
{
    use SoftDeletes;

    protected $table = 'orderables';

    protected $fillable = [
        'id',
        'order_id',
        'orderable_id',
        'orderable_type',
    ];

    public function orderable()
    {
        return $this->morphTo();
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
