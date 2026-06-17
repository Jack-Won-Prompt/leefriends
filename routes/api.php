<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\InboundController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\NoticeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\StoreLocatorController;
use App\Http\Controllers\Api\SupplyProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile App API (v1)
|--------------------------------------------------------------------------
| Consumed by the LEEFRIENDS Flutter app.
*/
Route::prefix('v1')->group(function () {
    Route::get('health', fn () => response()->json(['status' => 'ok']))->name('api.health');

    // 공개: 소비자 메뉴 / 공지 / 매장찾기
    Route::get('menus', [MenuController::class, 'index'])->name('api.menus.index');
    Route::get('menus/{menu}', [MenuController::class, 'show'])->name('api.menus.show');
    Route::get('categories', [MenuController::class, 'categories'])->name('api.categories');
    Route::get('notices', [NoticeController::class, 'index'])->name('api.notices.index');
    Route::get('notices/{notice}', [NoticeController::class, 'show'])->name('api.notices.show');
    Route::get('stores', [StoreLocatorController::class, 'index'])->name('api.stores.index');

    // 인증 (매장/포털 계정)
    Route::post('auth/login', [AuthController::class, 'login'])->name('api.auth.login');

    // 보호: 토큰 필요
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('api.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');

        // FCM 기기 토큰
        Route::post('device-tokens', [DeviceTokenController::class, 'store'])->name('api.device_tokens.store');
        Route::delete('device-tokens', [DeviceTokenController::class, 'destroy'])->name('api.device_tokens.destroy');

        // 매장 발주
        Route::get('supply-products', [SupplyProductController::class, 'index'])->name('api.supply_products.index');
        Route::get('orders', [OrderController::class, 'index'])->name('api.orders.index');
        Route::post('orders', [OrderController::class, 'store'])->name('api.orders.store');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('api.orders.show');
        Route::put('orders/{order}', [OrderController::class, 'update'])->name('api.orders.update');
        Route::delete('orders/{order}', [OrderController::class, 'destroy'])->name('api.orders.destroy');

        // 매입 내역
        Route::get('purchases', [PurchaseController::class, 'index'])->name('api.purchases.index');

        // 입고 (입고예정/배송중/입고처리)
        Route::get('inbound', [InboundController::class, 'index'])->name('api.inbound.index');
        Route::get('shipments/{shipment}', [InboundController::class, 'show'])->name('api.shipments.show');
        Route::post('shipments/{shipment}/receive', [InboundController::class, 'receive'])->name('api.shipments.receive');

        // 재고
        Route::get('inventory', [InventoryController::class, 'index'])->name('api.inventory.index');
        Route::get('inventory/movements', [InventoryController::class, 'movements'])->name('api.inventory.movements');
        Route::post('inventory/usage', [InventoryController::class, 'usage'])->name('api.inventory.usage');

        // 알림
        Route::get('notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('api.notifications.unread');
        Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('api.notifications.read_all');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('api.notifications.read');
    });
});
