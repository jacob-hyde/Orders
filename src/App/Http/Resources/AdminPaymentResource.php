<?php

namespace JacobHyde\Orders\App\Http\Resources;

use JacobHyde\Orders\Facades\Payment;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPaymentResource extends JsonResource
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
            'key' => $this->key,
            'payment_id' => $this->processor ? $this->processor->payment_identifier : null,
            'amount' => Payment::convertCentsToDollars($this->amount),
            'fee' => $this->fee ? Payment::convertCentsToDollars($this->fee) : null,
            'status' => $this->status,
            'processor' => $this->processor_method_type,
        ];
    }
}
