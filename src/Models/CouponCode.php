<?php

namespace KnotAShell\Orders\Models;

use KnotAShell\Orders\Models\ProductType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use eloquentFilter\QueryFilter\ModelFilters\Filterable;

class CouponCode extends Model
{
    use SoftDeletes;
    use Filterable;
    
    protected $table = 'coupon_codes';

    private static $whiteListFilter = [
        'id',
        'code',
        'amount',
        'deleted_at',
        'created_at'
    ];

    protected $fillable = [
        'active',
        'code',
        'product_type_id',
        'amount',
        'type',
        'applies_to_vender'
    ];

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public static function isCodeAvailable(string $code): bool
    {
        if (!is_string($code) && !is_numeric($code)) {
            return false;
        }

        $exists = self::where('code', $code)->first();
        if ($exists) {
            return false;
        }

        return true;
    }
}
