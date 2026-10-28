<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = Customer::withCount("orders")->orderBy("name")->get();

        return view("customers.index", [
            "customers" => $customers,
        ]);
    }

    public function create(): View
    {
        return view("customers.create");
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            "name" => ["required", "string", "max:255"],
            "email" => ["required", "email", "max:255", "unique:customers,email"],
        ]);

        Customer::create($validated);

        return redirect()->route("customers.index")->with("status", "Customer saved.");
    }

    public function show(Customer $customer): View
    {
        $orders = $customer->orders()->with("items.product")->latest()->get();

        return view("customers.show", [
            "customer" => $customer,
            "orders" => $orders,
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view("customers.edit", [
            "customer" => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            "name" => ["required", "string", "max:255"],
            "email" => ["required", "email", "max:255", "unique:customers,email,".$customer->id],
        ]);

        $customer->update($validated);

        return redirect()->route("customers.index")->with("status", "Customer saved.");
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->orders()->exists()) {
            return redirect()->route("customers.index")->with("error", "This customer has orders and cannot be deleted.");
        }

        $customer->delete();

        return redirect()->route("customers.index")->with("status", "Customer deleted.");
    }
}

