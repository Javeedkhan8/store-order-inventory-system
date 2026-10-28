<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController as WebOrderController;
use App\Http\Controllers\ProductLowStockController;
use App\Http\Controllers\ProductMasterController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });
});

require __DIR__.'/auth.php';

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/dashboard', [DashboardController::class, '__invoke'])->name('dashboard');
    Route::get('/products/low-stock', [ProductLowStockController::class, 'index'])->name('products.low-stock');
    Route::resource('products', ProductMasterController::class);
    Route::resource('customers', CustomerController::class);
    Route::post('/orders', [WebOrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/create', [WebOrderController::class, 'create'])->name('orders.create');
    Route::get('/orders', [WebOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [WebOrderController::class, 'show'])->name('orders.show');
});
