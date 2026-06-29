<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('shipping_box_count')->nullable()->after('store_amount'); // 박스 수
            $table->unsignedInteger('shipping_unit_price')->nullable()->after('shipping_box_count'); // 박스당 단가
            $table->unsignedInteger('shipping_fee')->default(0)->after('shipping_unit_price'); // 택배비 합계 = 박스 × 단가
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_box_count', 'shipping_unit_price', 'shipping_fee']);
        });
    }
};
