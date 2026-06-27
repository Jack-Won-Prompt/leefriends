<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 싯가(시세 변동) 품목 지원.
 *  - supply_products.is_market_price : 망고처럼 발주 시점에 단가 미정인 품목
 *  - order_items.price_pending        : 본사 단가 확정 대기 중인 주문 품목
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            $table->boolean('is_market_price')->default(false)->after('store_price');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('price_pending')->default(false)->after('store_line_amount');
        });
    }

    public function down(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            $table->dropColumn('is_market_price');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('price_pending');
        });
    }
};
