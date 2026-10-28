<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
            'quantity' => (int) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'tax_percentage' => (float) $this->tax_percentage,
            'line_subtotal' => (float) $this->line_subtotal,
            'line_tax' => (float) $this->line_tax,
            'line_total' => (float) $this->line_total,
        ];
    }
}
