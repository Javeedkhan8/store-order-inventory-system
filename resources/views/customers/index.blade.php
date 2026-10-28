@extends("layouts.app")

@section("title", "Customers | Store Manager")

@section("content")
    <section class="hero hero--compact">
        <div class="hero__copy">
            <p class="eyebrow">Customer management</p>
            <h1>Customers</h1>
            <p>Manage customer records and view their order history.</p>
        </div>
        <div class="hero__actions">
            <a class="button button--primary" href="{{ route('customers.create') }}">Add customer</a>
        </div>
    </section>

    @if (session("status"))
        <div class="flash flash--success">{{ session("status") }}</div>
    @endif
    @if (session("error"))
        <div class="flash flash--error">{{ session("error") }}</div>
    @endif

    <section class="panel">
        <div class="panel__header">
            <div>
                <h2>Customer list</h2>
                <p class="muted">{{ $customers->count() }} customer(s)</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Number of orders</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->email }}</td>
                            <td>{{ $customer->orders_count }}</td>
                            <td class="table__actions">
                                <a class="link-button" href="{{ route('customers.show', $customer) }}">View orders</a>
                                <a class="link-button" href="{{ route('customers.edit', $customer) }}">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No customers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
