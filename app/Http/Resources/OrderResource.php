<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
            'items' => $this->whenLoaded('items', fn () => OrderItemResource::collection($this->items)),
            'subtotal' => (float) $this->subtotal,
            'tax_total' => (float) $this->tax_total,
            'grand_total' => (float) $this->grand_total,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
