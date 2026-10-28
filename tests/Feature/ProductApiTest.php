<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_products(): void
    {
        Product::factory()->create(['name' => 'Coffee A']);
        Product::factory()->create(['name' => 'Coffee B']);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Coffee A')
            ->assertJsonPath('data.1.name', 'Coffee B');
    }

    public function test_it_creates_a_product_and_normalizes_its_fields(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => '  New Coffee  ',
            'code' => ' new-001 ',
            'price_per_unit' => 499.50,
            'tax_percentage' => 18,
            'stock_on_hand' => 12,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Coffee')
            ->assertJsonPath('data.code', 'NEW-001')
            ->assertJsonPath('data.price_per_unit', 499.5)
            ->assertJsonPath('data.stock_on_hand', 12);

        $this->assertDatabaseHas('products', [
            'code' => 'NEW-001',
            'stock_on_hand' => 12,
        ]);
    }

    public function test_it_validates_product_fields(): void
    {
        $this->postJson('/api/products', [
            'name' => '',
            'code' => '',
            'price_per_unit' => -1,
            'tax_percentage' => 101,
            'stock_on_hand' => -2,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'code',
                'price_per_unit',
                'tax_percentage',
                'stock_on_hand',
            ]);
    }

    public function test_it_rejects_a_duplicate_product_code(): void
    {
        Product::factory()->create(['code' => 'TAKEN-001']);

        $this->postJson('/api/products', [
            'name' => 'Another Coffee',
            'code' => 'taken-001',
            'price_per_unit' => 100,
            'tax_percentage' => 5,
            'stock_on_hand' => 1,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_it_updates_a_product_and_allows_its_own_code(): void
    {
        $product = Product::factory()->create([
            'name' => 'Old Name',
            'code' => 'SAME-001',
        ]);

        $this->putJson("/api/products/{$product->id}", [
            'name' => 'Updated Name',
            'code' => 'same-001',
            'price_per_unit' => 1000,
            'tax_percentage' => 5,
            'stock_on_hand' => 9,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.code', 'SAME-001')
            ->assertJsonPath('data.stock_on_hand', 9);

        $this->assertSame(9, $product->fresh()->stock_on_hand);
    }

    public function test_it_deletes_an_unused_product(): void
    {
        $product = Product::factory()->create();

        $this->deleteJson("/api/products/{$product->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_it_refuses_to_delete_a_product_with_order_history(): void
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $this->deleteJson("/api/products/{$product->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'This product has order history and cannot be deleted.');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
