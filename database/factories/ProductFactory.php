<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'code' => strtoupper(fake()->unique()->bothify('PRD-###-???')),
            'price_per_unit' => fake()->randomFloat(2, 5, 250),
            'tax_percentage' => fake()->randomElement([0, 5, 12, 18]),
            'stock_on_hand' => fake()->numberBetween(0, 100),
        ];
    }

    public function withStock(int $quantity): static
    {
        return $this->state(fn () => ['stock_on_hand' => $quantity]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock_on_hand' => 0]);
    }
}
