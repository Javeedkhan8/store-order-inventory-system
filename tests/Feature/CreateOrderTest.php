<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmationEmail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_order_submission_redirects_to_the_created_order(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $product = Product::factory()->withStock(5)->create([
            'price_per_unit' => 100.00,
            'tax_percentage' => 18.00,
        ]);

        $response = $this->actingAs($user)->post(route('orders.store'), [
            'customer_name' => 'Alice Johnson',
            'customer_email' => 'alice@example.com',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $order = Order::firstOrFail();

        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame(3, $product->fresh()->stock_on_hand);
        Queue::assertPushed(SendOrderConfirmationEmail::class, 1);
    }

    public function test_order_form_includes_saved_customers_for_email_lookup(): void
    {
        Customer::factory()->create([
            'name' => 'Morgan Patel',
            'email' => 'morgan@example.com',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('orders.create'));

        $response->assertOk()
            ->assertSee('Morgan Patel')
            ->assertSee('morgan@example.com');
    }

    public function test_it_creates_an_order_deducts_stock_and_queues_a_confirmation_email(): void
    {
        Queue::fake();

        $product = Product::factory()->withStock(10)->create([
            'name' => 'Test Coffee',
            'price_per_unit' => 100.00,
            'tax_percentage' => 18.00,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Alice Johnson',
            'customer_email' => 'Alice@Example.com',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.customer.email', 'alice@example.com')
            ->assertJsonPath('data.items.0.quantity', 2);

        $this->assertEquals(200, $response->json('data.subtotal'));
        $this->assertEquals(36, $response->json('data.tax_total'));
        $this->assertEquals(236, $response->json('data.grand_total'));
        $this->assertSame(8, $product->fresh()->stock_on_hand);

        $order = Order::firstOrFail();
        $this->assertSame(1, Customer::count());
        $this->assertSame('alice@example.com', $order->customer->email);
        $this->assertCount(1, $order->items);
        $this->assertEquals(236, $order->grand_total);

        Queue::assertPushed(
            SendOrderConfirmationEmail::class,
            fn (SendOrderConfirmationEmail $job) => $job->order->is($order),
        );
    }

    public function test_it_computes_totals_for_multiple_products_with_per_line_rounding(): void
    {
        Queue::fake();

        $coffee = Product::factory()->withStock(10)->create([
            'price_per_unit' => 10.00,
            'tax_percentage' => 10.00,
        ]);

        $cake = Product::factory()->withStock(10)->create([
            'price_per_unit' => 20.50,
            'tax_percentage' => 5.00,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Bob Smith',
            'customer_email' => 'bob@example.com',
            'items' => [
                ['product_id' => $coffee->id, 'quantity' => 2],
                ['product_id' => $cake->id, 'quantity' => 3],
            ],
        ]);

        $response->assertCreated();

        $this->assertEquals(81.50, $response->json('data.subtotal'));
        $this->assertEquals(5.08, $response->json('data.tax_total'));
        $this->assertEquals(86.58, $response->json('data.grand_total'));

        $this->assertSame(8, $coffee->fresh()->stock_on_hand);
        $this->assertSame(7, $cake->fresh()->stock_on_hand);
    }

    public function test_it_reuses_an_existing_customer_and_keeps_the_stored_name(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create([
            'name' => 'Alice Original',
            'email' => 'alice@example.com',
        ]);

        $product = Product::factory()->withStock(5)->create();

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Alice Updated',
            'customer_email' => 'ALICE@EXAMPLE.COM',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.customer.name', 'Alice Original');

        $this->assertSame(1, Customer::count());
        $this->assertSame($customer->id, Order::firstOrFail()->customer_id);
    }

    public function test_it_merges_duplicate_product_lines_into_one_order_item(): void
    {
        Queue::fake();

        $product = Product::factory()->withStock(10)->create([
            'price_per_unit' => 10.00,
            'tax_percentage' => 0,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Priya Patel',
            'customer_email' => 'priya@example.com',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.quantity', 3);

        $this->assertEquals(30, $response->json('data.subtotal'));
        $this->assertSame(7, $product->fresh()->stock_on_hand);
    }

    public function test_it_rejects_an_order_when_stock_is_insufficient(): void
    {
        Queue::fake();

        $product = Product::factory()->withStock(2)->create([
            'code' => 'COF-LOW-001',
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Mateo Garcia',
            'customer_email' => 'mateo@example.com',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('product_code', 'COF-LOW-001')
            ->assertJsonPath('requested_quantity', 3)
            ->assertJsonPath('available_stock', 2);

        $this->assertSame(0, Order::count());
        $this->assertSame(2, $product->fresh()->stock_on_hand);
        Queue::assertNothingPushed();
    }

    public function test_only_one_of_two_attempts_can_take_the_last_unit(): void
    {
        Queue::fake();

        $product = Product::factory()->withStock(1)->create();

        $payload = [
            'customer_name' => 'Sofia Muller',
            'customer_email' => 'sofia@example.com',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $this->postJson('/api/orders', $payload)->assertCreated();

        $this->postJson('/api/orders', $payload)
            ->assertStatus(409)
            ->assertJsonPath('available_stock', 0);

        $this->assertSame(1, Order::count());
        $this->assertSame(0, $product->fresh()->stock_on_hand);
        Queue::assertPushed(SendOrderConfirmationEmail::class, 1);
    }

    public function test_it_validates_the_order_payload(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer_name' => '',
            'customer_email' => 'not-an-email',
            'items' => [],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_name', 'customer_email', 'items']);
    }

    public function test_it_validates_each_product_line(): void
    {
        Product::factory()->create();

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'items' => [
                ['product_id' => 999999, 'quantity' => 0],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.product_id', 'items.0.quantity']);

        $this->assertSame(0, Order::count());
    }
}
