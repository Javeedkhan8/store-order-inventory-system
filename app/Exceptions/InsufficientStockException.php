<?php

namespace App\Exceptions;

use App\Models\Product;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly Product $product,
        public readonly int $requestedQuantity,
    ) {
        parent::__construct(sprintf(
            'Not enough stock for %s. Requested %d, available %d.',
            $product->name,
            $requestedQuantity,
            $product->stock_on_hand,
        ));
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'product_id' => $this->product->id,
            'product_code' => $this->product->code,
            'requested_quantity' => $this->requestedQuantity,
            'available_stock' => $this->product->stock_on_hand,
        ], 409);
    }
}
