@extends("layouts.app")

@section("title", "Orders | Store Manager")

@section("content")
    <section class="hero hero--compact">
        <div class="hero__copy">
            <p class="eyebrow">Order history</p>
            <h1>Orders</h1>
        </div>
        <div class="hero__actions">
            <a class="button button--primary" href="{{ route("orders.create") }}">Create Order</a>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <div>
                <h2>All orders</h2>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Number of items</th>
                        <th>Subtotal</th>
                        <th>Tax</th>
                        <th>Grand total</th>
                        <th>Created</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>#{{ $order->id }} {{ $order->order_number ? "(".$order->order_number.")" : "" }}</td>
                            <td>{{ $order->customer?->name }} ({{ $order->customer?->email }})</td>
                            <td>{{ $order->items->count() }}</td>
                            <td>₹{{ number_format($order->subtotal, 2) }}</td>
                            <td>₹{{ number_format($order->tax_total ?? 0, 2) }}</td>
                            <td>₹{{ number_format($order->grand_total, 2) }}</td>
                            <td>{{ $order->created_at }}</td>
                            <td><a class="link-button" href="{{ route("orders.show", $order) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
