<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LowStockProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            "threshold" => ["sometimes", "integer", "min:0"],
        ]);

        $threshold = (int) ($validated["threshold"] ?? config("inventory.low_stock_threshold"));

        $products = Product::query()
            ->where("stock_on_hand", "<", $threshold)
            ->orderBy("stock_on_hand")
            ->orderBy("name")
            ->get();

        return ProductResource::collection($products)->additional([
            "meta" => [
                "threshold" => $threshold,
                "count" => $products->count(),
            ],
        ]);
    }
}

