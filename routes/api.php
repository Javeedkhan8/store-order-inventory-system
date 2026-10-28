<?php

use App\Http\Controllers\Api\CustomerOrderHistoryController;
use App\Http\Controllers\Api\LowStockProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('/orders', [OrderController::class, 'store'])->name('api.orders.store');
Route::get('/customers/orders', [CustomerOrderHistoryController::class, 'index'])->name('customers.orders.index');
Route::get('/customers/{email}/orders', [CustomerOrderHistoryController::class, 'show'])->name('customers.orders.show');
Route::get('/products/low-stock', [LowStockProductController::class, 'index'])->name('products.low-stock');
Route::apiResource('products', ProductController::class)->except('show')->names('api.products');
