<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Jobs\SendOrderConfirmationEmail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function create(array $data): Order
    {
        $order = DB::transaction(function () use ($data) {
            $quantitiesByProduct = $this->mergeRequestedItems($data["items"]);
            ksort($quantitiesByProduct);

            $products = Product::query()
                ->whereIn("id", array_keys($quantitiesByProduct))
                ->lockForUpdate()
                ->get()
                ->keyBy("id");

            $customerName = $data["customer_name"] ?? ($data["customer"]["name"] ?? null);
            $customerEmail = $data["customer_email"] ?? ($data["customer"]["email"] ?? null);

            $customer = Customer::firstOrCreate(
                ["email" => strtolower($customerEmail)],
                ["name" => $customerName],
            );

            $order = Order::create([
                "order_number" => $this->nextOrderNumber(),
                "customer_id" => $customer->id,
                "subtotal" => 0,
                "tax_total" => 0,
                "grand_total" => 0,
            ]);

            $subtotal = 0.0;
            $taxTotal = 0.0;

            foreach ($quantitiesByProduct as $productId => $quantity) {
                $product = $products->get($productId);

                if (! $product) {
                    throw ValidationException::withMessages([
                        "items" => "Product {$productId} no longer exists.",
                    ]);
                }

                if ($product->stock_on_hand < $quantity) {
                    throw new InsufficientStockException($product, $quantity);
                }

                $lineSubtotal = round((float) $product->price_per_unit * $quantity, 2);
                $lineTax = round($lineSubtotal * (float) $product->tax_percentage / 100, 2);
                $lineTotal = round($lineSubtotal + $lineTax, 2);

                $order->items()->create([
                    "product_id" => $product->id,
                    "quantity" => $quantity,
                    "unit_price" => $product->price_per_unit,
                    "tax_percentage" => $product->tax_percentage,
                    "line_subtotal" => $lineSubtotal,
                    "line_tax" => $lineTax,
                    "line_total" => $lineTotal,
                ]);

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
            }

            $order->update([
                "subtotal" => round($subtotal, 2),
                "tax_total" => round($taxTotal, 2),
                "grand_total" => round($subtotal + $taxTotal, 2),
            ]);

            $this->deductStock($quantitiesByProduct);

            return $order;
        }, 3);

        SendOrderConfirmationEmail::dispatch($order);

        return $order;
    }

    private function mergeRequestedItems(array $items): array
    {
        $quantities = [];

        foreach ($items as $item) {
            $productId = (int) $item["product_id"];
            $quantities[$productId] = ($quantities[$productId] ?? 0) + (int) $item["quantity"];
        }

        return $quantities;
    }

    private function deductStock(array $quantitiesByProduct): void
    {
        foreach ($quantitiesByProduct as $productId => $quantity) {
            $updated = Product::query()
                ->whereKey($productId)
                ->where("stock_on_hand", ">=", $quantity)
                ->decrement("stock_on_hand", $quantity);

            if ($updated === 0) {
                throw new InsufficientStockException(
                    Product::findOrFail($productId),
                    $quantity,
                );
            }
        }
    }

    private function nextOrderNumber(): string
    {
        do {
            $orderNumber = "ORD-".now()->format("Ymd")."-".Str::upper(Str::random(6));
        } while (Order::where("order_number", $orderNumber)->exists());

        return $orderNumber;
    }
}

