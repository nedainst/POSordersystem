<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\TableController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PosController;

/*
|--------------------------------------------------------------------------
| Public Routes (Customer)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    $settings = \App\Models\SiteSetting::pluck('value', 'key')->toArray();
    return view('customer.welcome', compact('settings'));
})->name('home');

Route::get('/menu/{table}', [MenuController::class, 'show'])->name('menu.show');
Route::post('/order', [MenuController::class, 'order'])->name('order.store');
Route::get('/order/{order}/track', [MenuController::class, 'trackOrder'])->name('order.track');
Route::get('/order/{order}/payment', [MenuController::class, 'paymentPage'])->name('order.payment');
Route::post('/order/{order}/payment', [MenuController::class, 'selectPayment'])->name('order.payment.select');

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/orders/new', [DashboardController::class, 'getNewOrders'])->name('orders.new');
    Route::patch('/orders/{order}/status', [DashboardController::class, 'updateOrderStatus'])->name('orders.status');

    // Categories
    Route::resource('categories', CategoryController::class);

    // Menu Items
    Route::resource('menu-items', MenuItemController::class);
    Route::patch('/menu-items/{menu_item}/toggle', [MenuItemController::class, 'toggleAvailability'])->name('menu-items.toggle');

    // Tables
    Route::resource('tables', TableController::class);
    Route::get('/tables/{table}/qr', [TableController::class, 'generateQr'])->name('tables.qr');
    Route::get('/tables/{table}/print-qr', [TableController::class, 'printQr'])->name('tables.print-qr');
    Route::patch('/tables/{table}/reset', [TableController::class, 'resetTable'])->name('tables.reset');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/report', [OrderController::class, 'report'])->name('orders.report');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    // Payments
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{order}/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments/{order}', [PaymentController::class, 'store'])->name('payments.store');
    Route::patch('/payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
    Route::patch('/payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
    Route::post('/payments/{order}/quick-pay', [PaymentController::class, 'quickPay'])->name('payments.quick-pay');

    // POS (Point of Sale)
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('/pos/search', [PosController::class, 'searchItems'])->name('pos.search');
    Route::post('/pos/order', [PosController::class, 'processOrder'])->name('pos.process');
    Route::get('/pos/recent', [PosController::class, 'recentOrders'])->name('pos.recent');
});
