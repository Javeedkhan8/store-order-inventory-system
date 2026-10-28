<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductMasterController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()->orderBy('name')->get();

        $editingProduct = $request->filled('edit')
            ? Product::find($request->integer('edit'))
            : null;

        return view('products.index', [
            'products' => $products,
            'editingProduct' => $editingProduct,
            'lowStockThreshold' => (int) config('inventory.low_stock_threshold'),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        return redirect()
            ->route('products.index')
            ->with('status', 'Product created.');
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('products.index')
            ->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            return redirect()
                ->route('products.index')
                ->with('error', 'This product has order history and cannot be deleted.');
        }

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('status', 'Product deleted.');
    }
}
