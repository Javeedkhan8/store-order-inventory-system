<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductLowStockController extends Controller
{
    public function index(Request $request): View
    {
        $threshold = $request->filled("threshold") ? (int) $request->input("threshold") : (int) config("inventory.low_stock_threshold");
        if ($threshold < 0) {
            $threshold = (int) config("inventory.low_stock_threshold");
        }

        $products = Product::query()
            ->where("stock_on_hand", "<", $threshold)
            ->orderBy("stock_on_hand")
            ->orderBy("name")
            ->get();

        return view("products.low-stock", [
            "products" => $products,
            "threshold" => $threshold,
        ]);
    }
}

