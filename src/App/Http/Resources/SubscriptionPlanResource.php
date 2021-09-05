<?php

namespace JacobHyde\Orders\App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    /**
    * Transform the resource into an array.
    *
    * @param \Illuminate\Http\Request $request
    * @return array
    */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'price' => number_format($this->price, 2, '.', ','),
        ];
        if ($this->whenLoaded('planable')) {
            $data['planable'] = $this->planable->resource();
        }
        return $data;
    }
}