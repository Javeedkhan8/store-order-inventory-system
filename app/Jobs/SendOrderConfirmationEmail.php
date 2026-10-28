<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public Order $order)
    {
        $this->order = $order->withoutRelations();
    }

    public function handle(): void
    {
        $this->order->loadMissing('customer', 'items.product');

        Log::info('Order confirmation email simulated.', [
            'order_number' => $this->order->order_number,
            'customer_name' => $this->order->customer->name,
            'customer_email' => $this->order->customer->email,
            'items' => $this->order->items->map(fn ($item) => [
                'product' => $item->product->name,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
            ])->all(),
            'subtotal' => $this->order->subtotal,
            'tax_total' => $this->order->tax_total,
            'grand_total' => $this->order->grand_total,
        ]);
    }
}
