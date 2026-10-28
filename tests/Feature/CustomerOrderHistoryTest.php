<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CustomerOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_the_requested_customers_orders_newest_first(): void
    {
        Queue::fake();

        $alice = Customer::factory()->create(['email' => 'alice@example.com']);
        $bob = Customer::factory()->create(['email' => 'bob@example.com']);
        $product = Product::factory()->withStock(20)->create([
            'price_per_unit' => 10.00,
            'tax_percentage' => 0,
        ]);

        $firstOrder = $this->createOrderFor($alice, $product);
        $this->createOrderFor($bob, $product);
        $secondOrder = $this->createOrderFor($alice, $product);

        $response = $this->getJson('/api/customers/orders?email=ALICE@EXAMPLE.COM');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $secondOrder->id)
            ->assertJsonPath('data.1.id', $firstOrder->id)
            ->assertJsonPath('data.0.customer.email', 'alice@example.com')
            ->assertJsonPath('data.0.items.0.product.id', $product->id);
    }

    public function test_it_returns_an_empty_list_for_an_unknown_customer(): void
    {
        $this->getJson('/api/customers/orders?email=nobody@example.com')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_it_requires_a_valid_email(): void
    {
        $this->getJson('/api/customers/orders')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->getJson('/api/customers/orders?email=not-an-email')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    private function createOrderFor(Customer $customer, Product $product): Order
    {
        return app(OrderService::class)->create([
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);
    }
}
