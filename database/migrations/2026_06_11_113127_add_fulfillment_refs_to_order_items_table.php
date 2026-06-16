<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_order_id')->nullable()->after('order_id');
            $table->unsignedBigInteger('shipment_id')->nullable()->after('sales_order_id');
            $table->index('sales_order_id');
            $table->index('shipment_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['sales_order_id', 'shipment_id']);
        });
    }
};
