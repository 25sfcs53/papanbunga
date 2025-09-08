<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderStockController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::redirect('/', '/login');
Route::get('/dashboard', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user && isset($user->role) && $user->role === 'owner') {
            return app(DashboardController::class)->index();
        }
    }
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {

    // Dummy routes untuk uji middleware role
    Route::get('/owner-only', function () {
        return 'Owner Area';
    })->middleware('role:owner')->name('owner.test');

    Route::get('/manage', function () {
        return 'Admin or Owner Area';
    })->middleware('role:owner,admin')->name('manage.test');

    // Group owner-only
    Route::middleware('role:owner')->group(function () {
        // Product attribute: colors (owner-only) — moved under products/kelola-warna
        Route::prefix('/warna')->name('warna.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\ProductAttribute\ColorController::class, 'index'])->name('index');
            Route::get('create', [\App\Http\Controllers\Owner\ProductAttribute\ColorController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\ProductAttribute\ColorController::class, 'store'])->name('store');
            Route::get('{color}/edit', [\App\Http\Controllers\Owner\ProductAttribute\ColorController::class, 'edit'])->name('edit');
            Route::put('{color}', [\App\Http\Controllers\Owner\ProductAttribute\ColorController::class, 'update'])->name('update');
            Route::post('{color}/toggle', [\App\Http\Controllers\Owner\ProductAttribute\ColorController::class, 'toggle'])->name('toggle');
            Route::delete('{color}', [\App\Http\Controllers\Owner\ProductAttribute\ColorController::class, 'destroy'])->name('destroy');
        });
    Route::resource('products', ProductController::class);
    Route::post('products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');
    // AJAX helper: return counts per status for a given type+color
    Route::get('assets/counts', [AssetController::class, 'counts'])->name('assets.counts');
    Route::resource('assets', AssetController::class);
        Route::resource('components', ComponentController::class);
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });

    // Group admin+owner
    Route::middleware('role:owner,admin')->group(function () {
        Route::resource('customers', CustomerController::class);
        Route::resource('orders', OrderController::class);
    // Quick status change endpoint
    Route::post('orders/{order}/status', [OrderController::class, 'changeStatus'])->name('orders.changeStatus');
    // Additional routes for completing/returning an order (confirm assets/components)
    Route::get('orders/{order}/complete', [OrderController::class, 'completeForm'])->name('orders.complete.form');
    Route::post('orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    
    // Component stock management routes
    Route::post('orders/{order}/update-status', [OrderStockController::class, 'updateOrderStatus'])->name('orders.updateStatus');
    Route::post('orders/{order}/update-stock', [OrderStockController::class, 'updateStockManual'])->name('orders.updateStock');
    Route::post('preview-components', [OrderStockController::class, 'previewComponentUsage'])->name('orders.previewComponents');
    
        Route::resource('expenses', ExpenseController::class);
    });
});

require __DIR__.'/auth.php';
