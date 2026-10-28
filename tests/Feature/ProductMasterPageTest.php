<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMasterPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_it_shows_the_product_master_page(): void
    {
        Product::factory()->create(['name' => 'Visible Coffee']);

        $this->get('/products')
            ->assertOk()
            ->assertSee('Product master')
            ->assertSee('Visible Coffee');
    }

    public function test_it_creates_a_product_from_the_master_form(): void
    {
        $this->post('/products', [
            'name' => 'Web Coffee',
            'code' => ' web-001 ',
            'price_per_unit' => 250,
            'tax_percentage' => 5,
            'stock_on_hand' => 10,
        ])
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('status', 'Product created.');

        $this->assertDatabaseHas('products', [
            'name' => 'Web Coffee',
            'code' => 'WEB-001',
        ]);
    }

    public function test_it_updates_a_product_from_the_master_form(): void
    {
        $product = Product::factory()->create(['name' => 'Before']);

        $this->put(route('products.update', $product), [
            'name' => 'After',
            'code' => $product->code,
            'price_per_unit' => 300,
            'tax_percentage' => 12,
            'stock_on_hand' => 7,
        ])
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('status', 'Product updated.');

        $product->refresh();

        $this->assertSame('After', $product->name);
        $this->assertSame(7, $product->stock_on_hand);
    }

    public function test_it_returns_to_the_edit_form_when_validation_fails(): void
    {
        $product = Product::factory()->create();

        $this->from(route('products.index', ['edit' => $product->id]))
            ->put(route('products.update', $product), [
                'name' => '',
                'code' => $product->code,
                'price_per_unit' => 100,
                'tax_percentage' => 5,
                'stock_on_hand' => 1,
            ])
            ->assertRedirect(route('products.index', ['edit' => $product->id]))
            ->assertSessionHasErrors('name');
    }

    public function test_it_deletes_an_unused_product_from_the_master_form(): void
    {
        $product = Product::factory()->create();

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('status', 'Product deleted.');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_it_does_not_delete_a_product_with_order_history(): void
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('error', 'This product has order history and cannot be deleted.');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
