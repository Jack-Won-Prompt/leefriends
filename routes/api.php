<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\InboundController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\NoticeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\Seller;
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

        // 매장 홈 대시보드
        Route::get('store/dashboard', [\App\Http\Controllers\Api\StoreDashboardController::class, 'index'])->name('api.store.dashboard');

        // 매장 전자문서 (조회): 세금계산서 + 발주 거래명세서
        Route::get('store/tax-invoices', [\App\Http\Controllers\Api\StoreTaxInvoiceController::class, 'index'])->name('api.store.tax_invoices.index');
        Route::get('store/tax-invoices/{invoice}', [\App\Http\Controllers\Api\StoreTaxInvoiceController::class, 'show'])->name('api.store.tax_invoices.show');
        Route::get('orders/{order}/statement', [OrderController::class, 'statement'])->name('api.orders.statement');

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

        // 일정(캘린더) — 본사/매장/공급처 각자 소속
        Route::get('schedules', [\App\Http\Controllers\Api\ScheduleController::class, 'index'])->name('api.schedules.index');
        Route::post('schedules', [\App\Http\Controllers\Api\ScheduleController::class, 'store'])->name('api.schedules.store');
        Route::put('schedules/{schedule}', [\App\Http\Controllers\Api\ScheduleController::class, 'update'])->name('api.schedules.update');
        Route::delete('schedules/{schedule}', [\App\Http\Controllers\Api\ScheduleController::class, 'destroy'])->name('api.schedules.destroy');

        // 알림
        Route::get('notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('api.notifications.unread');
        Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('api.notifications.read_all');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('api.notifications.read');

        // 채팅 (본사 ↔ 매장/공급처)
        Route::prefix('chat')->name('api.chat.')->group(function () {
            Route::get('conversations', [ChatController::class, 'conversations'])->name('conversations');
            Route::get('open', [ChatController::class, 'open'])->name('open');
            Route::get('unread', [ChatController::class, 'unread'])->name('unread');
            Route::get('conversations/{conversation}/messages', [ChatController::class, 'messages'])->name('messages');
            Route::post('conversations/{conversation}/messages', [ChatController::class, 'send'])->name('send');
        });

        // 본사/공급처 (판매자) — 받은 발주/판매주문 처리 + 출고
        Route::prefix('seller')->name('api.seller.')->group(function () {
            Route::get('dashboard', [Seller\DashboardController::class, 'index'])->name('dashboard');
            Route::get('sales', [Seller\SalesController::class, 'index'])->name('sales');

            // 상품 관리 (본사 CRUD/승인, 공급처 등록)
            Route::get('products', [Seller\ProductController::class, 'index'])->name('products.index');
            Route::post('products', [Seller\ProductController::class, 'store'])->name('products.store');
            Route::put('products/{product}', [Seller\ProductController::class, 'update'])->name('products.update');
            Route::delete('products/{product}', [Seller\ProductController::class, 'destroy'])->name('products.destroy');
            Route::patch('products/{product}/approve', [Seller\ProductController::class, 'approve'])->name('products.approve');
            Route::patch('products/{product}/reject', [Seller\ProductController::class, 'reject'])->name('products.reject');

            // 카테고리 관리 (본사)
            Route::get('categories', [Seller\CategoryController::class, 'index'])->name('categories.index');
            Route::post('categories', [Seller\CategoryController::class, 'store'])->name('categories.store');
            Route::put('categories/{category}', [Seller\CategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}', [Seller\CategoryController::class, 'destroy'])->name('categories.destroy');

            // 공급처 관리 (본사)
            Route::get('suppliers', [Seller\SupplierController::class, 'index'])->name('suppliers.index');
            Route::post('suppliers/invite', [Seller\SupplierController::class, 'invite'])->name('suppliers.invite');
            Route::put('suppliers/{supplier}', [Seller\SupplierController::class, 'update'])->name('suppliers.update');
            Route::delete('suppliers/{supplier}', [Seller\SupplierController::class, 'destroy'])->name('suppliers.destroy');
            Route::post('suppliers/{supplier}/reinvite', [Seller\SupplierController::class, 'reinvite'])->name('suppliers.reinvite');

            // 매장 관리 (본사)
            Route::get('stores', [Seller\StoreManageController::class, 'index'])->name('stores.index');
            Route::post('stores/invite', [Seller\StoreManageController::class, 'invite'])->name('stores.invite');
            Route::put('stores/{store}', [Seller\StoreManageController::class, 'update'])->name('stores.update');
            Route::post('stores/{store}/reinvite', [Seller\StoreManageController::class, 'reinvite'])->name('stores.reinvite');

            // 공지 관리 (본사)
            Route::get('notices', [Seller\NoticeController::class, 'index'])->name('notices.index');
            Route::post('notices', [Seller\NoticeController::class, 'store'])->name('notices.store');
            Route::delete('notices/{notice}', [Seller\NoticeController::class, 'destroy'])->name('notices.destroy');

            // 가맹문의 (본사)
            Route::get('inquiries', [Seller\InquiryController::class, 'index'])->name('inquiries.index');
            Route::get('inquiries/{inquiry}', [Seller\InquiryController::class, 'show'])->name('inquiries.show');
            Route::patch('inquiries/{inquiry}', [Seller\InquiryController::class, 'update'])->name('inquiries.update');
            Route::delete('inquiries/{inquiry}', [Seller\InquiryController::class, 'destroy'])->name('inquiries.destroy');

            Route::get('orders', [Seller\OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [Seller\OrderController::class, 'show'])->name('orders.show');
            Route::patch('orders/{order}/items/{item}', [Seller\OrderController::class, 'updateItem'])->name('orders.items.update');
            Route::patch('orders/{order}/items/{item}/price', [Seller\OrderController::class, 'setItemPrice'])->name('orders.items.price');
            Route::patch('orders/{order}/items/{item}/edit', [Seller\OrderController::class, 'editItem'])->name('orders.items.edit');
            Route::patch('orders/{order}/shipping', [Seller\OrderController::class, 'updateShipping'])->name('orders.shipping');
            Route::post('orders/{order}/tax-invoice', [Seller\OrderController::class, 'issueForOrder'])->name('orders.tax_invoice');
            Route::post('orders/{order}/statement-email', [Seller\OrderController::class, 'statementEmail'])->name('orders.statement_email');
            Route::post('orders/{order}/payment-request', [Seller\OrderController::class, 'paymentRequest'])->name('orders.payment_request');

            // 매장 주문 변경 확인(반영)
            Route::get('order-changes', [Seller\OrderChangeController::class, 'index'])->name('order_changes.index');
            Route::post('order-changes/ack-all', [Seller\OrderChangeController::class, 'ackAll'])->name('order_changes.ack_all');
            Route::post('order-changes/{change}/ack', [Seller\OrderChangeController::class, 'ack'])->name('order_changes.ack');

            Route::get('sales-orders', [Seller\SalesOrderController::class, 'index'])->name('sales_orders.index');
            Route::get('sales-orders/{salesOrder}', [Seller\SalesOrderController::class, 'show'])->name('sales_orders.show');
            Route::patch('sales-orders/{salesOrder}/confirm', [Seller\SalesOrderController::class, 'confirm'])->name('sales_orders.confirm');

            Route::get('couriers', [Seller\ShipmentController::class, 'couriers'])->name('couriers.index');
            Route::get('shipments', [Seller\ShipmentController::class, 'index'])->name('shipments.index');
            Route::get('shipments/candidates', [Seller\ShipmentController::class, 'candidates'])->name('shipments.candidates');
            Route::post('shipments', [Seller\ShipmentController::class, 'store'])->name('shipments.store');
            Route::get('shipments/{shipment}', [Seller\ShipmentController::class, 'show'])->name('shipments.show');
            Route::patch('shipments/{shipment}/confirm', [Seller\ShipmentController::class, 'confirm'])->name('shipments.confirm');

            // 전자세금계산서 — 발행 대상 조회 / 발행 / 취소 / 이력
            Route::get('tax-invoices', [Seller\TaxInvoiceController::class, 'index'])->name('tax_invoices.index');
            Route::get('tax-invoices/stores', [Seller\TaxInvoiceController::class, 'stores'])->name('tax_invoices.stores');
            Route::get('tax-invoices/issuable', [Seller\TaxInvoiceController::class, 'issuable'])->name('tax_invoices.issuable');
            Route::post('tax-invoices/issue', [Seller\TaxInvoiceController::class, 'issue'])->name('tax_invoices.issue');
            Route::get('tax-invoices/{invoice}', [Seller\TaxInvoiceController::class, 'show'])->name('tax_invoices.show');
            Route::post('tax-invoices/{invoice}/cancel', [Seller\TaxInvoiceController::class, 'cancel'])->name('tax_invoices.cancel');

            // 공급사 발주 현황 (본사 전용)
            Route::get('supplier-orders', [Seller\SupplierOrderController::class, 'index'])->name('supplier_orders.index');
            Route::get('supplier-orders/{salesOrder}', [Seller\SupplierOrderController::class, 'show'])->name('supplier_orders.show');

            // 본사 계좌 입금확인 — 계좌 거래내역 수집 + 입금자 매핑 + 주문 대사 (본사 전용)
            Route::get('bank', [Seller\BankController::class, 'index'])->name('bank.index');
            Route::post('bank/request', [Seller\BankController::class, 'requestJob'])->name('bank.request');
            Route::get('bank/jobs/{job}/state', [Seller\BankController::class, 'jobState'])->name('bank.job_state');
            Route::post('bank/map', [Seller\BankController::class, 'mapDepositor'])->name('bank.map');
            Route::post('bank/match', [Seller\BankController::class, 'match'])->name('bank.match');
            Route::delete('bank/deposits/{deposit}/match', [Seller\BankController::class, 'unmatch'])->name('bank.unmatch');
            Route::post('bank/auto-match', [Seller\BankController::class, 'autoMatch'])->name('bank.auto_match');
            Route::get('bank/flat-rate-url', [Seller\BankController::class, 'flatRateUrl'])->name('bank.flat_rate_url');

            // 본사 매출/매입 — 홈택스 전자세금계산서 수집·조회 (본사 전용)
            Route::get('hometax', [Seller\HometaxController::class, 'index'])->name('hometax.index');
            Route::post('hometax/request', [Seller\HometaxController::class, 'requestJob'])->name('hometax.request');
            Route::get('hometax/jobs/{job}/state', [Seller\HometaxController::class, 'jobState'])->name('hometax.job_state');
            Route::get('hometax/detail', [Seller\HometaxController::class, 'detail'])->name('hometax.detail');
            Route::get('hometax/cert-url', [Seller\HometaxController::class, 'certUrl'])->name('hometax.cert_url');
            Route::get('hometax/flat-rate-url', [Seller\HometaxController::class, 'flatRateUrl'])->name('hometax.flat_rate_url');

            // 거래명세서 — 작성 / 전송 / 발행 / 이력
            Route::get('statements', [Seller\StatementController::class, 'index'])->name('statements.index');
            Route::get('statements/catalog', [Seller\StatementController::class, 'catalog'])->name('statements.catalog');
            Route::post('statements', [Seller\StatementController::class, 'store'])->name('statements.store');
            Route::get('statements/{id}', [Seller\StatementController::class, 'show'])->name('statements.show');
            Route::post('statements/{id}/send', [Seller\StatementController::class, 'send'])->name('statements.send');
            Route::post('statements/{id}/issue', [Seller\StatementController::class, 'issue'])->name('statements.issue');
        });
    });
});
