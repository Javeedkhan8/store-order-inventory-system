<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::with(["customer", "items"])->latest()->get();

        return view("orders.index", [
            "orders" => $orders,
        ]);
    }

    public function create(): View
    {
        $customers = Customer::query()
            ->orderBy("name")
            ->get(["name", "email"])
            ->map(fn (Customer $customer) => [
                "name" => $customer->name,
                "email" => $customer->email,
            ])
            ->values();
        $products = Product::orderBy("name")->get();
        $lowStockThreshold = (int) config("inventory.low_stock_threshold");

        return view("orders.create", [
            "customers" => $customers,
            "products" => $products,
            "lowStockThreshold" => $lowStockThreshold,
        ]);
    }

    public function store(StoreOrderRequest $request, OrderService $orderService): RedirectResponse
    {
        try {
            $order = $orderService->create($request->validated());

            return redirect()->route("orders.show", $order)->with("status", "Order created successfully.");
        } catch (\Throwable $e) {
            return back()->withInput()->with("error", $e->getMessage());
        }
    }

    public function show(Order $order): View
    {
        $order->load(["customer", "items.product"]);

        return view("orders.show", [
            "order" => $order,
        ]);
    }
}

