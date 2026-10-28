<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_products_below_the_configured_threshold(): void
    {
        config(['inventory.low_stock_threshold' => 5]);

        $outOfStock = Product::factory()->withStock(0)->create(['name' => 'Out of Stock']);
        $lowStock = Product::factory()->withStock(4)->create(['name' => 'Low Stock']);
        Product::factory()->withStock(5)->create(['name' => 'At Threshold']);
        Product::factory()->withStock(6)->create(['name' => 'Above Threshold']);

        $response = $this->getJson('/api/products/low-stock');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.threshold', 5)
            ->assertJsonPath('meta.count', 2)
            ->assertJsonPath('data.0.id', $outOfStock->id)
            ->assertJsonPath('data.1.id', $lowStock->id);
    }

    public function test_the_threshold_can_be_overridden_for_a_single_request(): void
    {
        config(['inventory.low_stock_threshold' => 5]);

        Product::factory()->withStock(1)->create();
        Product::factory()->withStock(4)->create();
        Product::factory()->withStock(5)->create();

        $response = $this->getJson('/api/products/low-stock?threshold=6');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.threshold', 6);
    }

    public function test_it_validates_the_threshold(): void
    {
        $this->getJson('/api/products/low-stock?threshold=not-a-number')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('threshold');

        $this->getJson('/api/products/low-stock?threshold=-1')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('threshold');
    }
}
