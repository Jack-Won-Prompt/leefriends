<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();           // 주문번호
            $table->unsignedBigInteger('store_id');         // 발주 매장
            $table->unsignedBigInteger('user_id')->nullable(); // 발주자

            // 전체 진행상태: pending(접수) | processing(처리중) | shipping(배송중) | completed(완료) | canceled(취소)
            $table->string('status')->default('pending');

            $table->unsignedInteger('store_amount')->default(0);  // 매장 결제총액(출고가 합계)
            $table->unsignedInteger('supply_amount')->default(0); // 공급가 합계(본사 원가)
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('store_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
