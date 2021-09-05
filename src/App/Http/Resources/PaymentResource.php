<?php

namespace JacobHyde\Orders\App\Http\Resources;

use JacobHyde\Orders\ARPayment;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'key' => $this->key,
            'payment_id' => $this->processor->payment_identifier,
            'amount' => ARPayment::convertCentsToDollars($this->amount),
            'fee' => $this->fee ? ARPayment::convertCentsToDollars($this->fee) : null,
            'status' => $this->status,
        ];
    }
}
