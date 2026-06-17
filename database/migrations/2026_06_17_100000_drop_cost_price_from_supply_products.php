<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 원가 = 공급가(supply_price)로 일원화 — 별도 cost_price 컬럼 제거.
 * (기존 cost_price 값은 supply_price 로 이전 완료)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            $table->unsignedInteger('cost_price')->default(0)->after('supply_price');
        });
    }
};
