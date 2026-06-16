<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 주문 유형: normal(일반 발주) | sample(샘플 주문, 가격 미표시)
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_type', 20)->default('normal')->after('status');
            $table->index('order_type');
        });
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('order_type', 20)->default('normal')->after('status');
            $table->index('order_type');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['order_type']);
            $table->dropColumn('order_type');
        });
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropIndex(['order_type']);
            $table->dropColumn('order_type');
        });
    }
};
