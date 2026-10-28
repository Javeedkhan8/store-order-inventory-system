@extends('layouts.app')

@section('title', 'Store Manager | Counter Dashboard')

@section('content')
    <section class="hero">
        <div class="hero__copy">
            <p class="eyebrow">Store order &amp; inventory</p>
            <h1>Keep the counter moving.</h1>
            <p>Record a customer order, watch stock update instantly, and look up past purchases in one place.</p>
        </div>
        <div class="hero__stats">
            <div class="stat">
                <span class="stat__value" id="productCount">{{ $products->count() }}</span>
                <span class="stat__label">Products</span>
            </div>
            <div class="stat">
                <span class="stat__value" id="lowStockCount">{{ $products->where('stock_on_hand', '<', $lowStockThreshold)->count() }}</span>
                <span class="stat__label">Low stock</span>
            </div>
            <div class="stat">
                <span class="stat__value" id="totalUnits">{{ $products->sum('stock_on_hand') }}</span>
                <span class="stat__label">Units on hand</span>
            </div>
        </div>
    </section>

    <section class="panel dashboard-catalog">
        <div class="panel__header">
            <div>
                <h2>Product catalog</h2>
                <p class="muted">{{ $products->count() }} items available to order.</p>
            </div>
            <a class="button button--soft" href="{{ route('products.index') }}">Manage products</a>
        </div>

        <div class="product-grid">
            @forelse ($products as $product)
                <article class="product-card {{ $product->stock_on_hand < $lowStockThreshold ? 'is-low' : '' }}">
                    <div class="product-card__top">
                        <span class="code">{{ $product->code }}</span>
                        <span class="stock-badge {{ $product->stock_on_hand < $lowStockThreshold ? 'stock-badge--low' : '' }}">
                            {{ $product->stock_on_hand }} in stock
                        </span>
                    </div>
                    <h3>{{ $product->name }}</h3>
                    <div class="product-card__meta">
                        <div>
                            <dt>Price</dt>
                            <dd>₹{{ number_format((float) $product->price_per_unit, 2) }}</dd>
                        </div>
                        <div>
                            <dt>Tax</dt>
                            <dd>{{ rtrim(rtrim(number_format((float) $product->tax_percentage, 2), '0'), '.') }}%</dd>
                        </div>
                    </div>
                </article>
            @empty
                <p class="empty-state">No products yet. Add products to start taking orders.</p>
            @endforelse
        </div>
    </section>
@endsection
