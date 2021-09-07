<?php

namespace KnotAShell\Orders\App\Http\Resources;

use KnotAShell\Orders\Payment;
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
            'amount' => Payment::convertCentsToDollars($this->amount),
            'fee' => $this->fee ? Payment::convertCentsToDollars($this->fee) : null,
            'status' => $this->status,
        ];
    }
}
