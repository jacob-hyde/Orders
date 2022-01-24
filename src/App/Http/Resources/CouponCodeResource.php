<?php

namespace JacobHyde\Orders\App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CouponCodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'service' => config('orders.service_name'),
            'active' => $this->active,
            'code' => $this->code,
            'product_type' => $this->product_type,
            'amount' => $this->amount,
            'type' => $this->type,
            'applies_to_vendor' => $this->applies_to_vendor,
            'deleted' => $this->deleted_at ? true : false,
            'created_at' => $this->created_at->toIso8601String()
        ];
    }
}
