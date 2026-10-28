@extends("layouts.app")

@section("title", $customer->name." Orders | Store Manager")

@section("content")
    <section class="hero hero--compact">
        <div class="hero__copy">
            <p class="eyebrow">Customer orders</p>
            <h1>{{ $customer->name }}</h1>
            <p>{{ $customer->email }}</p>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <div>
                <h2>Orders</h2>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Items</th>
                        <th>Subtotal</th>
                        <th>Tax</th>
                        <th>Grand total</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>#{{ $order->id }} {{ $order->order_number ? " (".$order->order_number.")" : "" }}</td>
                            <td>{{ $order->items->count() }}</td>
                            <td>₹{{ number_format($order->subtotal, 2) }}</td>
                            <td>₹{{ number_format($order->tax_total ?? 0, 2) }}</td>
                            <td>₹{{ number_format($order->grand_total, 2) }}</td>
                            <td>{{ $order->created_at }}</td>
                            <td><a class="link-button" href="{{ route("orders.show", $order) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
