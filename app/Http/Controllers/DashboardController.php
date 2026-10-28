<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $lowStockThreshold = (int) config("inventory.low_stock_threshold");

        $products = Product::query()
            ->orderBy("name")
            ->get();

        $productData = $products->map(fn (Product $product) => [
            "id" => $product->id,
            "name" => $product->name,
            "code" => $product->code,
            "price" => (float) $product->price_per_unit,
            "tax" => (float) $product->tax_percentage,
            "stock" => (int) $product->stock_on_hand,
        ]);

        $totalProducts = Product::count();
        $totalCustomers = Customer::count();
        $totalOrders = Order::count();
        $lowStockProductCount = Product::where("stock_on_hand", "<", $lowStockThreshold)->count();
        $lowStockProducts = Product::where("stock_on_hand", "<", $lowStockThreshold)->orderBy("stock_on_hand")->orderBy("name")->get();

        return view("dashboard", [
            "products" => $products,
            "productData" => $productData,
            "lowStockThreshold" => $lowStockThreshold,
            "totalProducts" => $totalProducts,
            "totalCustomers" => $totalCustomers,
            "totalOrders" => $totalOrders,
            "lowStockProductCount" => $lowStockProductCount,
            "lowStockProducts" => $lowStockProducts,
        ]);
    }
}

