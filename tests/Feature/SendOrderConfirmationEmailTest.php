<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmationEmail;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class SendOrderConfirmationEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_a_simulated_order_confirmation(): void
    {
        Log::spy();

        $product = Product::factory()->create(['name' => 'Test Coffee']);
        $order = Order::factory()->create();

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 10.00,
            'tax_percentage' => 0,
            'line_subtotal' => 20.00,
            'line_tax' => 0,
            'line_total' => 20.00,
        ]);

        (new SendOrderConfirmationEmail($order))->handle();

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Order confirmation email simulated.', Mockery::on(
                fn (array $context) => $context['order_number'] === $order->order_number
                    && $context['customer_email'] === $order->customer->email
                    && $context['items'][0]['quantity'] === 2
                    && (float) $context['grand_total'] === 0.0,
            ));
    }
}
