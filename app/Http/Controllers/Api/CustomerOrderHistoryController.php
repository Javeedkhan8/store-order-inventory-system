<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerOrderHistoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            "email" => ["required", "string", "email"],
        ]);

        $email = strtolower($validated["email"]);

        $orders = Order::query()
            ->whereHas("customer", fn ($query) => $query->where("email", $email))
            ->with(["customer", "items.product"])
            ->latest()
            ->orderByDesc("id")
            ->get();

        return OrderResource::collection($orders);
    }

    public function show(string $email): AnonymousResourceCollection
    {
        $email = strtolower($email);

        $orders = Order::query()
            ->whereHas("customer", fn ($query) => $query->where("email", $email))
            ->with(["customer", "items.product"])
            ->latest()
            ->orderByDesc("id")
            ->get();

        return OrderResource::collection($orders);
    }
}

