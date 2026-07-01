<?php

use App\Http\Controllers\FranchiseController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Portal;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site
|--------------------------------------------------------------------------
*/
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/brand', [PageController::class, 'brand'])->name('brand');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/account-deletion', [PageController::class, 'accountDeletion'])->name('account.deletion');

Route::get('/menu', [MenuController::class, 'index'])->name('menu');
Route::get('/store', [StoreController::class, 'index'])->name('store');

Route::get('/notice', [NoticeController::class, 'index'])->name('notice.index');
Route::get('/notice/{notice}', [NoticeController::class, 'show'])->name('notice.show');

Route::get('/franchise', [FranchiseController::class, 'index'])->name('franchise');
Route::post('/franchise/inquiry', [FranchiseController::class, 'store'])->name('franchise.store');
Route::get('/franchise/thanks', [FranchiseController::class, 'thanks'])->name('franchise.thanks');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [Admin\AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [Admin\AuthController::class, 'login'])->name('login.attempt');
    Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::get('inquiries', [Admin\InquiryController::class, 'index'])->name('inquiries.index');
        Route::get('inquiries/{inquiry}', [Admin\InquiryController::class, 'show'])->name('inquiries.show');
        Route::patch('inquiries/{inquiry}', [Admin\InquiryController::class, 'update'])->name('inquiries.update');
        Route::delete('inquiries/{inquiry}', [Admin\InquiryController::class, 'destroy'])->name('inquiries.destroy');

        Route::resource('notices', Admin\NoticeController::class)->except(['show', 'create', 'edit']);
        Route::resource('menus', Admin\MenuController::class)->except(['show', 'create', 'edit']);
        Route::resource('stores', Admin\StoreController::class)->except(['show', 'create', 'edit']);
    });
});

/*
|--------------------------------------------------------------------------
| B2B 발주포털 (본사 / 매장 / 공급처)
|--------------------------------------------------------------------------
*/
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('login', [Portal\AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [Portal\AuthController::class, 'login'])->name('login.attempt');
    Route::post('logout', [Portal\AuthController::class, 'logout'])->name('logout');

    // 공급처 초대 수락 (비밀번호 설정) — 비로그인 접근
    Route::get('invite/{token}', [Portal\InvitationController::class, 'show'])->name('invite.show');
    Route::post('invite/{token}', [Portal\InvitationController::class, 'accept'])->name('invite.accept');

    Route::middleware('role:hq,store,supplier')->group(function () {
        Route::get('/', [Portal\DashboardController::class, 'index'])->name('dashboard');

        // 알림 (전 역할)
        Route::get('notifications', [Portal\NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [Portal\NotificationController::class, 'readAll'])->name('notifications.read_all');
        Route::post('notifications/{notification}/read', [Portal\NotificationController::class, 'read'])->name('notifications.read');

        // 일정 관리(캘린더) — 본사/매장/공급처 각자 소속 일정
        Route::get('schedules', [Portal\ScheduleController::class, 'index'])->name('schedules.index');
        Route::post('schedules', [Portal\ScheduleController::class, 'store'])->name('schedules.store');
        Route::patch('schedules/{schedule}', [Portal\ScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('schedules/{schedule}', [Portal\ScheduleController::class, 'destroy'])->name('schedules.destroy');

        // 직원(계정) 관리 — 본사/매장/공급처 각자 소속 직원
        Route::get('staff', [Portal\StaffController::class, 'index'])->name('staff.index');
        Route::post('staff', [Portal\StaffController::class, 'store'])->name('staff.store');
        Route::patch('staff/{user}', [Portal\StaffController::class, 'update'])->name('staff.update');
        Route::delete('staff/{user}', [Portal\StaffController::class, 'destroy'])->name('staff.destroy');

        // 실시간 채팅 (본사 ↔ 매장 / 본사 ↔ 공급처)
        Route::get('chat', [Portal\ChatController::class, 'index'])->name('chat.index');
        Route::post('chat/{conversation}/send', [Portal\ChatController::class, 'send'])->name('chat.send');
        Route::get('chat/{conversation}/poll', [Portal\ChatController::class, 'poll'])->name('chat.poll');

        // 본사 공지사항 열람 (매장/공급처)
        Route::middleware('role:store,supplier')->group(function () {
            Route::get('notices', [Portal\NoticeController::class, 'index'])->name('notices.index');
            Route::get('notices/{notice}', [Portal\NoticeController::class, 'show'])->name('notices.show');
        });

        // 매장 주문 변경 확인(반영) - 본사/공급처
        Route::middleware('role:hq,supplier')->group(function () {
            Route::get('order-changes', [Portal\OrderChangeController::class, 'index'])->name('order_changes.index');
            Route::post('order-changes/ack-all', [Portal\OrderChangeController::class, 'ackAll'])->name('order_changes.ack_all');
            Route::post('order-changes/{change}/ack', [Portal\OrderChangeController::class, 'ack'])->name('order_changes.ack');
        });

        // 매장
        Route::middleware('role:store')->prefix('store')->name('store.')->group(function () {
            Route::get('orders', [Portal\Store\OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/create', [Portal\Store\OrderController::class, 'create'])->name('orders.create');
            Route::post('orders', [Portal\Store\OrderController::class, 'store'])->name('orders.store');
            // 샘플 주문 (가격 미표시) — 접수는 orders.store가 order_type으로 분기
            Route::get('sample-orders', [Portal\Store\OrderController::class, 'sampleIndex'])->name('sample_orders.index');
            Route::get('sample-orders/create', [Portal\Store\OrderController::class, 'sampleCreate'])->name('sample_orders.create');
            Route::get('orders/{order}', [Portal\Store\OrderController::class, 'show'])->name('orders.show');
            Route::get('orders/{order}/statement', [Portal\Store\OrderController::class, 'statement'])->name('orders.statement');
            Route::get('orders/{order}/edit', [Portal\Store\OrderController::class, 'edit'])->name('orders.edit');
            Route::put('orders/{order}', [Portal\Store\OrderController::class, 'update'])->name('orders.update');
            Route::delete('orders/{order}', [Portal\Store\OrderController::class, 'destroy'])->name('orders.destroy');
            Route::get('purchases', [Portal\Store\PurchaseController::class, 'index'])->name('purchases');

            // 입고예정 / 배송중
            Route::get('inbound', [Portal\Store\InboundController::class, 'index'])->name('inbound');

            // 입고처리 / 재고
            Route::get('shipments/{shipment}', [Portal\Store\ReceivingController::class, 'show'])->name('shipments.show');
            Route::post('shipments/{shipment}/receive', [Portal\Store\ReceivingController::class, 'receive'])->name('shipments.receive');
            Route::get('inventory', [Portal\Store\InventoryController::class, 'index'])->name('inventory.index');
            Route::get('inventory/movements', [Portal\Store\InventoryController::class, 'movements'])->name('inventory.movements');
            Route::post('inventory/usage', [Portal\Store\InventoryController::class, 'usage'])->name('inventory.usage');

            // 본사가 매장 앞으로 발행한 세금계산서 확인
            Route::get('tax-invoices', [Portal\Store\TaxInvoiceController::class, 'index'])->name('tax_invoices.index');
            Route::get('tax-invoices/{invoice}', [Portal\Store\TaxInvoiceController::class, 'show'])->name('tax_invoices.show');
        });

        // 본사
        Route::middleware('role:hq')->prefix('hq')->name('hq.')->group(function () {
            Route::get('orders', [Portal\Hq\OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [Portal\Hq\OrderController::class, 'show'])->name('orders.show');
            Route::patch('orders/{order}/items/{item}', [Portal\Hq\OrderController::class, 'updateItem'])->name('orders.items.update');
            Route::patch('orders/{order}/items/{item}/price', [Portal\Hq\OrderController::class, 'setItemPrice'])->name('orders.items.price');
            Route::patch('orders/{order}/items/{item}/edit', [Portal\Hq\OrderController::class, 'editItem'])->name('orders.items.edit');
            Route::patch('orders/{order}/shipping', [Portal\Hq\OrderController::class, 'updateShipping'])->name('orders.shipping');
            Route::get('orders/{order}/statement/pdf', [Portal\Hq\OrderController::class, 'statementPdf'])->name('orders.statement.pdf');
            Route::post('orders/{order}/statement/email', [Portal\Hq\OrderController::class, 'statementEmail'])->name('orders.statement.email');
            Route::get('sales', [Portal\Hq\SalesController::class, 'index'])->name('sales');
            Route::get('sales/store/{store}', [Portal\Hq\SalesController::class, 'storeOrders'])->name('sales.store_orders');

            // 판매주문
            // 공급사 발주 현황 (공급사별 판매주문)
            Route::get('supplier-orders', [Portal\Hq\SupplierOrderController::class, 'index'])->name('supplier_orders.index');

            Route::get('sales-orders', [Portal\Hq\SalesOrderController::class, 'index'])->name('sales_orders.index');
            Route::patch('sales-orders/{salesOrder}/confirm', [Portal\Hq\SalesOrderController::class, 'confirm'])->name('sales_orders.confirm');

            // 출고
            Route::get('shipments', [Portal\Hq\ShipmentController::class, 'index'])->name('shipments.index');
            Route::get('shipments/create', [Portal\Hq\ShipmentController::class, 'create'])->name('shipments.create');
            Route::post('shipments', [Portal\Hq\ShipmentController::class, 'store'])->name('shipments.store');
            Route::get('shipments/{shipment}', [Portal\Hq\ShipmentController::class, 'show'])->name('shipments.show');
            Route::patch('shipments/{shipment}/confirm', [Portal\Hq\ShipmentController::class, 'confirm'])->name('shipments.confirm');
            Route::get('shipments/{shipment}/statement', [Portal\Hq\ShipmentController::class, 'statement'])->name('shipments.statement');
            Route::resource('products', Portal\Hq\ProductController::class)->except(['show', 'create', 'edit']);
            // 공급처 등록 물품 승인/반려
            Route::patch('products/{product}/approve', [Portal\Hq\ProductController::class, 'approve'])->name('products.approve');
            Route::patch('products/{product}/reject', [Portal\Hq\ProductController::class, 'reject'])->name('products.reject');
            // 품목 카테고리(대분류) 관리
            Route::get('categories', [Portal\Hq\CategoryController::class, 'index'])->name('categories.index');
            Route::post('categories', [Portal\Hq\CategoryController::class, 'store'])->name('categories.store');
            Route::patch('categories/{category}', [Portal\Hq\CategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}', [Portal\Hq\CategoryController::class, 'destroy'])->name('categories.destroy');

            // 택배사 관리 (직접 배송 포함)
            Route::get('couriers', [Portal\Hq\CourierController::class, 'index'])->name('couriers.index');
            Route::post('couriers', [Portal\Hq\CourierController::class, 'store'])->name('couriers.store');
            Route::patch('couriers/{courier}', [Portal\Hq\CourierController::class, 'update'])->name('couriers.update');
            Route::delete('couriers/{courier}', [Portal\Hq\CourierController::class, 'destroy'])->name('couriers.destroy');
            Route::post('suppliers/invite', [Portal\Hq\SupplierController::class, 'invite'])->name('suppliers.invite');
            Route::post('suppliers/{supplier}/reinvite', [Portal\Hq\SupplierController::class, 'reinvite'])->name('suppliers.reinvite');
            Route::resource('suppliers', Portal\Hq\SupplierController::class)->except(['show', 'create', 'edit']);
            // 매장 관리 + 초대
            Route::get('stores', [Portal\Hq\StoreController::class, 'index'])->name('stores.index');
            Route::post('stores/invite', [Portal\Hq\StoreController::class, 'invite'])->name('stores.invite');
            Route::post('stores/{store}/reinvite', [Portal\Hq\StoreController::class, 'reinvite'])->name('stores.reinvite');
            Route::patch('stores/{store}', [Portal\Hq\StoreController::class, 'update'])->name('stores.update');
            Route::delete('stores/{store}', [Portal\Hq\StoreController::class, 'destroy'])->name('stores.destroy');
            Route::get('invoices', [Portal\Hq\InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('invoices/{invoice}/print', [Portal\Hq\InvoiceController::class, 'print'])->name('invoices.print');

            // 세금계산서 발행 (본사 → 매장)
            Route::get('tax-invoices', [Portal\Hq\TaxInvoiceController::class, 'index'])->name('tax_invoices.index');
            Route::get('tax-invoices/create', [Portal\Hq\TaxInvoiceController::class, 'create'])->name('tax_invoices.create');
            Route::post('tax-invoices', [Portal\Hq\TaxInvoiceController::class, 'store'])->name('tax_invoices.store');
            Route::post('tax-invoices/{invoice}/cancel', [Portal\Hq\TaxInvoiceController::class, 'cancel'])->name('tax_invoices.cancel');
            Route::get('tax-invoices/{invoice}/print', [Portal\Hq\TaxInvoiceController::class, 'printInvoice'])->name('tax_invoices.print');
            Route::post('orders/{order}/tax-invoice', [Portal\Hq\TaxInvoiceController::class, 'issueForOrder'])->name('tax_invoices.issue');

            // 매출/매입 관리 (홈택스 세금계산서 수집)
            Route::get('hometax', [Portal\Hq\HometaxTaxinvoiceController::class, 'index'])->name('hometax.index');
            Route::post('hometax/request', [Portal\Hq\HometaxTaxinvoiceController::class, 'requestJob'])->name('hometax.request');
            Route::get('hometax/jobs/{job}/state', [Portal\Hq\HometaxTaxinvoiceController::class, 'jobState'])->name('hometax.job_state');
            Route::get('hometax/detail', [Portal\Hq\HometaxTaxinvoiceController::class, 'detail'])->name('hometax.detail');
            Route::get('hometax/cert', [Portal\Hq\HometaxTaxinvoiceController::class, 'certUrl'])->name('hometax.cert');
            Route::get('hometax/flatrate', [Portal\Hq\HometaxTaxinvoiceController::class, 'flatRateUrl'])->name('hometax.flatrate');
            Route::post('statements/{statement}/tax-invoice', [Portal\Hq\TaxInvoiceController::class, 'issueForStatement'])->name('tax_invoices.issue_statement');

            // 거래명세서 (매장·품목 선택 → PDF 미리보기/이메일 전송 + 발송 이력)
            Route::get('statements', [Portal\Hq\StatementController::class, 'index'])->name('statements.index');
            Route::get('statements/create', [Portal\Hq\StatementController::class, 'create'])->name('statements.create');
            Route::post('statements/preview', [Portal\Hq\StatementController::class, 'preview'])->name('statements.preview');
            Route::post('statements/send', [Portal\Hq\StatementController::class, 'send'])->name('statements.send');
            Route::get('statements/{statement}/pdf', [Portal\Hq\StatementController::class, 'pdf'])->name('statements.pdf');
            Route::get('statements/{statement}/print', [Portal\Hq\StatementController::class, 'print'])->name('statements.print');
            Route::post('statements/{statement}/resend', [Portal\Hq\StatementController::class, 'resend'])->name('statements.resend');

            // 공지사항 발송 (매장/공급처 대상)
            Route::get('notices', [Portal\Hq\NoticeController::class, 'index'])->name('notices.index');
            Route::post('notices', [Portal\Hq\NoticeController::class, 'store'])->name('notices.store');
            Route::delete('notices/{notice}', [Portal\Hq\NoticeController::class, 'destroy'])->name('notices.destroy');

            // 창업 문의 (온라인 접수 확인/관리)
            Route::get('inquiries', [Portal\Hq\InquiryController::class, 'index'])->name('inquiries.index');
            Route::get('inquiries/{inquiry}', [Portal\Hq\InquiryController::class, 'show'])->name('inquiries.show');
            Route::patch('inquiries/{inquiry}', [Portal\Hq\InquiryController::class, 'update'])->name('inquiries.update');
            Route::delete('inquiries/{inquiry}', [Portal\Hq\InquiryController::class, 'destroy'])->name('inquiries.destroy');
        });

        // 공급처
        Route::middleware('role:supplier')->prefix('supplier')->name('supplier.')->group(function () {
            Route::get('orders', [Portal\Supplier\OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [Portal\Supplier\OrderController::class, 'show'])->name('orders.show');
            // 자사 공급 품목 배송상태 변경
            Route::patch('fulfillment/{item}', [Portal\Supplier\OrderController::class, 'updateItem'])->name('fulfillment.update');

            // 물품(재료) 등록/관리 — 본사 승인 후 매장 발주 가능
            Route::resource('products', Portal\Supplier\ProductController::class)->except(['show', 'create', 'edit']);

            Route::get('sales', [Portal\Supplier\SalesController::class, 'index'])->name('sales');
            Route::get('sales/store/{store}', [Portal\Supplier\SalesController::class, 'storeOrders'])->name('sales.store_orders');

            // 판매주문
            Route::get('sales-orders', [Portal\Supplier\SalesOrderController::class, 'index'])->name('sales_orders.index');
            Route::patch('sales-orders/{salesOrder}/confirm', [Portal\Supplier\SalesOrderController::class, 'confirm'])->name('sales_orders.confirm');

            // 출고
            Route::get('shipments', [Portal\Supplier\ShipmentController::class, 'index'])->name('shipments.index');
            Route::get('shipments/create', [Portal\Supplier\ShipmentController::class, 'create'])->name('shipments.create');
            Route::post('shipments', [Portal\Supplier\ShipmentController::class, 'store'])->name('shipments.store');
            Route::get('shipments/{shipment}', [Portal\Supplier\ShipmentController::class, 'show'])->name('shipments.show');
            Route::patch('shipments/{shipment}/confirm', [Portal\Supplier\ShipmentController::class, 'confirm'])->name('shipments.confirm');
            Route::get('shipments/{shipment}/statement', [Portal\Supplier\ShipmentController::class, 'statement'])->name('shipments.statement');
            Route::get('invoices', [Portal\Supplier\InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('invoices/create', [Portal\Supplier\InvoiceController::class, 'create'])->name('invoices.create');
            Route::post('invoices', [Portal\Supplier\InvoiceController::class, 'store'])->name('invoices.store');
            Route::post('invoices/{invoice}/cancel', [Portal\Supplier\InvoiceController::class, 'cancel'])->name('invoices.cancel');
            Route::get('invoices/{invoice}/print', [Portal\Supplier\InvoiceController::class, 'print'])->name('invoices.print');

            // 거래명세서 (작성→저장→선택 발행)
            Route::get('statements', [Portal\Supplier\StatementController::class, 'index'])->name('statements.index');
            Route::get('statements/create', [Portal\Supplier\StatementController::class, 'create'])->name('statements.create');
            Route::post('statements', [Portal\Supplier\StatementController::class, 'store'])->name('statements.store');
            Route::post('statements/issue-selected', [Portal\Supplier\StatementController::class, 'issueBulk'])->name('statements.issue_bulk');
            Route::post('statements/{statement}/issue', [Portal\Supplier\StatementController::class, 'issue'])->name('statements.issue');
            Route::post('statements/{statement}/email', [Portal\Supplier\StatementController::class, 'email'])->name('statements.email');
            Route::get('statements/{statement}/pdf', [Portal\Supplier\StatementController::class, 'pdf'])->name('statements.pdf');
            Route::get('statements/{statement}/print', [Portal\Supplier\StatementController::class, 'print'])->name('statements.print');
            Route::delete('statements/{statement}', [Portal\Supplier\StatementController::class, 'destroy'])->name('statements.destroy');
        });
    });
});
