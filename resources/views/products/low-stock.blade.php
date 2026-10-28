@extends("layouts.app")

@section("title", "Low Stock Products | Store Manager")

@section("content")
    <section class="hero hero--compact">
        <div class="hero__copy">
            <p class="eyebrow">Inventory</p>
            <h1>Low Stock Products</h1>
            <p>Products where stock < threshold</p>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <div>
                <h2>Filter</h2>
            </div>
        </div>
        <form method="GET" action="{{ route("products.low-stock") }}" class="form-grid" style="align-items: end;">
            <label class="field">
                <span>Threshold</span>
                <input type="number" name="threshold" min="0" value="{{ $threshold }}">
            </label>
            <button type="submit" class="button button--primary">Apply</button>
        </form>
    </section>

    <section class="panel">
        <div class="panel__header">
            <div>
                <h2>Products</h2>
                <p class="muted">Threshold: {{ $threshold }}</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Code</th>
                        <th>Current stock</th>
                        <th>Threshold</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->code }}</td>
                            <td>{{ $product->stock_on_hand }}</td>
                            <td>{{ $threshold }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No low stock products.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
