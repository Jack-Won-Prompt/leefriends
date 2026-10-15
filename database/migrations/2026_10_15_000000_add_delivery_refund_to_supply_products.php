<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 상품에 배송주기·환불정책 추가 — 상품 상세(매장 발주 화면)에 함께 표시.
 * 객단가는 기존 store_price(매장 판매가)를 사용한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            $table->string('delivery_cycle', 100)->nullable()->after('spec'); // 배송주기(예: 주2회, 입고 차수별)
            $table->text('refund_policy')->nullable()->after('delivery_cycle'); // 환불정책
        });
    }

    public function down(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            $table->dropColumn(['delivery_cycle', 'refund_policy']);
        });
    }
};
