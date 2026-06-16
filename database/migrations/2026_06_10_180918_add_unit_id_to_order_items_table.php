<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // 주문 시 선택된 단위(추적용). 단위명/가격은 기존 unit/*_price 컬럼에 스냅샷됨.
            $table->unsignedBigInteger('supply_product_unit_id')->nullable()->after('supply_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('supply_product_unit_id');
        });
    }
};
