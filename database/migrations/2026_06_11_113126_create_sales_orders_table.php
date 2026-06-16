<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 판매주문: 구매주문(orders)을 이행 주체(본사/공급처)별로 분할
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('sales_order_no')->unique();
            $table->unsignedBigInteger('order_id');           // 원 구매주문
            $table->unsignedBigInteger('store_id');           // 발주 매장(배송지)
            $table->string('seller_type')->default('hq');     // hq | supplier
            $table->unsignedBigInteger('supplier_id')->nullable();

            // created(접수) | confirmed(확인) | shipped(출고/배송시작) | received(입고완료) | canceled
            $table->string('status')->default('created');

            $table->unsignedInteger('item_count')->default(0);
            $table->unsignedInteger('store_amount')->default(0);  // 출고가 합계
            $table->unsignedInteger('supply_amount')->default(0); // 공급가 합계
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['seller_type', 'supplier_id']);
            $table->index('store_id');
            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
