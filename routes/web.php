<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:admin')->group(function () {
        Route::get('categories/data', [CategoryController::class, 'data'])->name('categories.data');
        Route::get('units/data', [UnitController::class, 'data'])->name('units.data');
        Route::get('warehouses/data', [WarehouseController::class, 'data'])->name('warehouses.data');
        Route::get('partners/data', [PartnerController::class, 'data'])->name('partners.data');
        Route::get('items/data', [ItemController::class, 'data'])->name('items.data');
        Route::get('users/data', [UserController::class, 'data'])->name('users.data');
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('units', UnitController::class)->except('show');
        Route::resource('warehouses', WarehouseController::class)->except('show');
        Route::resource('partners', PartnerController::class)->except('show');
        Route::resource('items', ItemController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');
    });

    Route::middleware('role:purchasing,admin')->group(function () {
        Route::get('purchase-orders/data', [PurchaseOrderController::class, 'data'])->name('purchase-orders.data');
        Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::get('purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
        Route::put('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
        Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    });

    Route::middleware('role:warehouse,admin')->group(function () {
        Route::get('receipts/data', [ReceiptController::class, 'data'])->name('receipts.data');
        Route::get('receipts/purchase-orders/data', [ReceiptController::class, 'receivablePurchaseOrders'])->name('receipts.purchase-orders.data');
        Route::get('receipts', [ReceiptController::class, 'index'])->name('receipts.index');
        Route::get('purchase-orders/{purchaseOrder}/receive', [ReceiptController::class, 'create'])->name('receipts.create');
        Route::post('receipts', [ReceiptController::class, 'store'])->name('receipts.store');
        Route::get('receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
        Route::get('stock-balances/data', [StockController::class, 'data'])->name('stocks.data');
        Route::get('stock-balances', [StockController::class, 'index'])->name('stocks.index');
        Route::get('deliveries/data', [DeliveryController::class, 'data'])->name('deliveries.data');
        Route::get('deliveries/sales-orders/data', [DeliveryController::class, 'confirmedOrders'])->name('deliveries.sales-orders.data');
        Route::get('deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
        Route::get('sales-orders/{salesOrder}/deliver', [DeliveryController::class, 'create'])->name('deliveries.create');
        Route::post('deliveries', [DeliveryController::class, 'store'])->name('deliveries.store');
        Route::get('deliveries/{delivery}', [DeliveryController::class, 'show'])->name('deliveries.show');
    });

    Route::middleware('role:sales,admin')->group(function () {
        Route::get('sales-orders/data', [SalesOrderController::class, 'data'])->name('sales-orders.data');
        Route::get('sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
        Route::get('sales-orders/create', [SalesOrderController::class, 'create'])->name('sales-orders.create');
        Route::post('sales-orders', [SalesOrderController::class, 'store'])->name('sales-orders.store');
        Route::get('sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
        Route::get('sales-orders/{salesOrder}/edit', [SalesOrderController::class, 'edit'])->name('sales-orders.edit');
        Route::put('sales-orders/{salesOrder}', [SalesOrderController::class, 'update'])->name('sales-orders.update');
        Route::post('sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])->name('sales-orders.confirm');
        Route::post('sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel'])->name('sales-orders.cancel');
    });

    Route::middleware('role:finance,admin')->prefix('reports')->name('reports.')->group(function () {
        Route::get('profit-loss', [ReportController::class, 'profitAndLoss'])->name('profit-loss');
        Route::get('profit-loss/data', [ReportController::class, 'profitAndLossData'])->name('profit-loss.data');
        Route::get('stock-card', [ReportController::class, 'stockCard'])->name('stock-card');
        Route::get('stock-card/data', [ReportController::class, 'stockCardData'])->name('stock-card.data');
    });
});
