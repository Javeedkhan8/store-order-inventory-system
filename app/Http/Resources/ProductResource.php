<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'price_per_unit' => (float) $this->price_per_unit,
            'tax_percentage' => (float) $this->tax_percentage,
            'stock_on_hand' => (int) $this->stock_on_hand,
        ];
    }
}
