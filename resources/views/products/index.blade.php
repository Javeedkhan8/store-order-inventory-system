@extends('layouts.app')

@section('title', 'Product Master | Store Manager')

@section('content')
    @php
        $isEditing = $editingProduct !== null;
        $lowStockCount = $products->where('stock_on_hand', '<', $lowStockThreshold)->count();
        $inventoryValue = $products->sum(fn ($product) => (float) $product->price_per_unit * $product->stock_on_hand);
    @endphp

    <section class="hero hero--compact">
        <div class="hero__copy">
            <p class="eyebrow">Master data</p>
            <h1>Product master</h1>
            <p>Add new products, update prices and tax rates, and keep stock levels accurate.</p>
        </div>
        <div class="hero__stats">
            <div class="stat">
                <span class="stat__value">{{ $products->count() }}</span>
                <span class="stat__label">Products</span>
            </div>
            <div class="stat">
                <span class="stat__value">{{ $lowStockCount }}</span>
                <span class="stat__label">Low stock</span>
            </div>
            <div class="stat">
                <span class="stat__value">₹{{ number_format((float) $inventoryValue, 0) }}</span>
                <span class="stat__label">Stock value</span>
            </div>
        </div>
    </section>

    @if (session('status'))
        <div class="flash flash--success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="flash flash--error">{{ session('error') }}</div>
    @endif

    <div class="admin-grid">
        <section class="panel admin-form-panel">
            <div class="panel__header">
                <div>
                    <h2>{{ $isEditing ? 'Edit product' : 'Add product' }}</h2>
                    <p class="muted">
                        {{ $isEditing ? 'Update the selected master record.' : 'Create a new product for the catalog.' }}
                    </p>
                </div>
                @if ($isEditing)
                    <span class="pill pill--quiet">Editing #{{ $editingProduct->id }}</span>
                @endif
            </div>

            <form
                method="POST"
                action="{{ $isEditing ? route('products.update', $editingProduct) : route('products.store') }}"
                class="product-form"
            >
                @csrf
                @if ($isEditing)
                    @method('PUT')
                @endif

                @if ($errors->any())
                    <div class="message message--error">Please fix the highlighted fields.</div>
                @endif

                <label class="field">
                    <span>Product name</span>
                    <input
                        name="name"
                        value="{{ old('name', $editingProduct->name ?? '') }}"
                        placeholder="Ethiopian Yirgacheffe Beans 1kg"
                        maxlength="255"
                        required
                    >
                    @error('name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </label>

                <label class="field">
                    <span>Product code</span>
                    <input
                        name="code"
                        value="{{ old('code', $editingProduct->code ?? '') }}"
                        placeholder="COF-YIR-1KG"
                        maxlength="255"
                        required
                    >
                    @error('code')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </label>

                <div class="form-grid">
                    <label class="field">
                        <span>Price per unit (₹)</span>
                        <input
                            name="price_per_unit"
                            type="number"
                            step="0.01"
                            min="0"
                            value="{{ old('price_per_unit', $editingProduct->price_per_unit ?? '') }}"
                            required
                        >
                        @error('price_per_unit')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="field">
                        <span>Tax percentage</span>
                        <input
                            name="tax_percentage"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            value="{{ old('tax_percentage', $editingProduct->tax_percentage ?? '') }}"
                            required
                        >
                        @error('tax_percentage')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </label>
                </div>

                <label class="field">
                    <span>Stock on hand</span>
                    <input
                        name="stock_on_hand"
                        type="number"
                        step="1"
                        min="0"
                        value="{{ old('stock_on_hand', $editingProduct->stock_on_hand ?? 0) }}"
                        required
                    >
                    @error('stock_on_hand')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </label>

                <div class="form-actions">
                    <button type="submit" class="button button--primary">
                        {{ $isEditing ? 'Save changes' : 'Add product' }}
                    </button>

                    @if ($isEditing)
                        <a href="{{ route('products.index') }}" class="button button--soft">Cancel</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="panel admin-list-panel">
            <div class="panel__header">
                <div>
                    <h2>Catalog</h2>
                    <p class="muted">{{ $products->count() }} product(s) in the master record.</p>
                </div>
                <span class="pill pill--quiet">Low stock: below {{ $lowStockThreshold }}</span>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="num">Price</th>
                            <th class="num">Tax</th>
                            <th class="num">Stock</th>
                            <th class="actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr class="{{ $product->stock_on_hand < $lowStockThreshold ? 'is-low' : '' }}">
                                <td>
                                    <span class="table-title">{{ $product->name }}</span>
                                    <span class="code">{{ $product->code }}</span>
                                </td>
                                <td class="num">₹{{ number_format((float) $product->price_per_unit, 2) }}</td>
                                <td class="num">{{ rtrim(rtrim(number_format((float) $product->tax_percentage, 2), '0'), '.') }}%</td>
                                <td class="num">
                                    <span class="stock-badge {{ $product->stock_on_hand < $lowStockThreshold ? 'stock-badge--low' : '' }}">
                                        {{ $product->stock_on_hand }}
                                    </span>
                                </td>
                                <td class="actions">
                                    <a class="link-button" href="{{ route('products.index', ['edit' => $product->id]) }}">Edit</a>
                                    <form
                                        method="POST"
                                        action="{{ route('products.destroy', $product) }}"
                                        class="inline-form"
                                        onsubmit="return confirm('Delete this product?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="link-button link-button--danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <p class="empty-state empty-state--small">No products yet. Add the first one with the form.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
