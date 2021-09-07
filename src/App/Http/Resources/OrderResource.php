<?php

namespace KnotAShell\Orders\App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OrderResource extends JsonResource
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
            'uuid' => $this->uuid,
            'amount' => $this->amount,
            'invoice_url' => $this->invoice_url ? Storage::disk(config('orders.invoice_bucket'))->temporaryUrl($this->invoice_url, now()->addSeconds(3600)) : null,
            'created_at' => $this->created_at->toiso8601string(),
            'status' => $this->status,
        ];
    }
}
