<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 5, 250);
        $taxPercentage = fake()->randomElement([0, 5, 12, 18]);

        $lineSubtotal = round($quantity * $unitPrice, 2);
        $lineTax = round($lineSubtotal * $taxPercentage / 100, 2);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_percentage' => $taxPercentage,
            'line_subtotal' => $lineSubtotal,
            'line_tax' => $lineTax,
            'line_total' => round($lineSubtotal + $lineTax, 2),
        ];
    }
}
