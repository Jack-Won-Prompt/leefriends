<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
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

    // 공개: 소비자 메뉴
    Route::get('menus', [MenuController::class, 'index'])->name('api.menus.index');
    Route::get('menus/{menu}', [MenuController::class, 'show'])->name('api.menus.show');
    Route::get('categories', [MenuController::class, 'categories'])->name('api.categories');

    // 인증 (매장/포털 계정)
    Route::post('auth/login', [AuthController::class, 'login'])->name('api.auth.login');

    // 보호: 토큰 필요
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('api.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');

        // 매장 발주
        Route::get('supply-products', [SupplyProductController::class, 'index'])->name('api.supply_products.index');
        Route::get('orders', [OrderController::class, 'index'])->name('api.orders.index');
        Route::post('orders', [OrderController::class, 'store'])->name('api.orders.store');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('api.orders.show');
        Route::put('orders/{order}', [OrderController::class, 'update'])->name('api.orders.update');
        Route::delete('orders/{order}', [OrderController::class, 'destroy'])->name('api.orders.destroy');
    });
});
