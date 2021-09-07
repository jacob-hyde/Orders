<?php

namespace KnotAShell\Orders\App\Services;

use KnotAShell\Orders\Models\CouponCode;

class CouponCodeService
{
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED = 'fixed';

    public $couponCode;

    public function __construct(CouponCode $couponCode)
    {
        $this->couponCode = $couponCode;
    }

    /**
     * @param $toVendor
     * @param $fee
     * @return float|int
     */
    public function discountBuyerCharge($toVendor, $fee) {
        $totalPrice = $toVendor + $fee;

        if ($this->couponCode->applies_to_vendor) {
            if ($this->couponCode->type == self::TYPE_FIXED) {
                // Vendor Fixed
                return $totalPrice - $this->couponCode->amount;
            }
            // Vendor Percentage
            return $totalPrice - ($totalPrice * $this->couponCode->amount / 100);
        }

        $newFee = $this->discountFee($toVendor, $fee);
        return $totalPrice - $fee + $newFee;
    }

    /**
     * @param $toVendor
     * @param $fee
     */
    public function discountVendorPayout($toVendor, $fee) {
        if (!$this->couponCode->applies_to_vendor) {
            return $toVendor;
        }

        if ($this->couponCode->type === self::TYPE_PERCENTAGE) {
            return $toVendor - ($toVendor * ($this->couponCode->amount / 100));
        }

        $totalPrice = $toVendor + $fee;
        $ratio = $fee / $totalPrice;
        $buyerCost = $toVendor + $fee - $this->couponCode->amount;
        $discountFee = $buyerCost * $ratio;
        return $toVendor + $fee - $this->couponCode->amount - $discountFee;
    }

    /**
     * @param $toVendor
     * @param $fee
     * @return float|int
     */
    public function discountFee($toVendor, $fee) {
        if ($this->couponCode->type === self::TYPE_PERCENTAGE) {
            $totalPrice = $toVendor + $fee;
            $couponDiscount = $totalPrice * ($this->couponCode->amount / 100);

            // We cover full cost of coupon discount
            $newFee = $fee - $couponDiscount;
            return $newFee > 0 ? $newFee : 0;
        }

        if ($this->couponCode->applies_to_vendor) {
            $totalPrice = $toVendor + $fee;
            $ratio = $fee / $totalPrice;
            $buyerCost = $toVendor + $fee - $this->couponCode->amount;
            return $buyerCost * $ratio;
        }

        $newFee = $fee - $this->couponCode->amount;
        return $newFee > 0 ? $newFee : 0;
    }
}
