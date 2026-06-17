<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            // 원가 (실제 매입/생산 원가) — 마진 계산용
            $table->unsignedInteger('cost_price')->default(0)->after('supply_price');
        });
    }

    public function down(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
