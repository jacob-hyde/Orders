<?php

namespace KnotAShell\Orders\App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
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
            'name' => $this->subscription_plan->name,
            'status' => $this->stripe_status,
            'ends_at' => $this->ends_at ? $this->ends_at->toiso8601string() : null,
            'bills_next' => $this->bills_next,
            'created_at' => $this->created_at->toiso8601string(),
            'service' => config('orders.service_name'),
            'price' => number_format($this->subscription_plan->planable->price, 2, '.', ','),
            'orders' => OrderResource::collection($this->whenLoaded('orders')),
        ];
    }
}
