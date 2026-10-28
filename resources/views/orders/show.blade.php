@extends("layouts.app")

@section("title", "Order Details | Store Manager")

@section("content")
    @if (session("status"))
        <div class="flash flash--success">{{ session("status") }}</div>
    @endif
    <section class="hero hero--compact">
        <div class="hero__copy">
            <p class="eyebrow">Order</p>
            <h1>Order #{{ $order->id }} {{ $order->order_number ? "(".$order->order_number.")" : "" }}</h1>
            <p>{{ $order->created_at }}</p>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <div>
                <h2>Customer</h2>
            </div>
        </div>
        <p><strong>Name:</strong> {{ $order->customer?->name }}</p>
        <p><strong>Email:</strong> {{ $order->customer?->email }}</p>
    </section>

    <section class="panel">
        <div class="panel__header">
            <div>
                <h2>Items</h2>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit price</th>
                        <th>Tax %</th>
                        <th>Tax amount</th>
                        <th>Line total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item->product?->name }} {{ $item->product?->code ? "(".$item->product?->code.")" : "" }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>₹{{ number_format($item->unit_price, 2) }}</td>
                            <td>{{ $item->tax_percentage }}%</td>
                            <td>₹{{ number_format($item->line_tax, 2) }}</td>
                            <td>₹{{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <dl class="totals" style="margin-top: 1rem;">
            <div><dt>Subtotal</dt><dd>₹{{ number_format($order->subtotal, 2) }}</dd></div>
            <div><dt>Tax</dt><dd>₹{{ number_format($order->tax_total ?? 0, 2) }}</dd></div>
            <div class="totals__grand"><dt>Grand Total</dt><dd>₹{{ number_format($order->grand_total, 2) }}</dd></div>
        </dl>
    </section>
@endsection
