<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('supply_product_id')->nullable();

            // 주문 시점 스냅샷
            $table->string('product_name');
            $table->string('unit')->default('개');
            $table->string('supply_type')->default('hq');       // hq | supplier
            $table->unsignedBigInteger('supplier_id')->nullable(); // 담당 공급처(스냅샷)
            $table->string('supplier_name')->nullable();

            $table->unsignedInteger('qty')->default(1);
            $table->unsignedInteger('store_unit_price')->default(0);  // 출고가
            $table->unsignedInteger('supply_unit_price')->default(0); // 공급가
            $table->unsignedInteger('store_line_amount')->default(0);
            $table->unsignedInteger('supply_line_amount')->default(0);

            // 품목별 배송상태: pending(대기) | shipping(배송중) | delivered(배송완료)
            $table->string('fulfillment_status')->default('pending');
            $table->timestamp('shipped_at')->nullable();

            // 세금계산서 연결 (공급처→본사 청구 완료 시)
            $table->unsignedBigInteger('tax_invoice_id')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('supplier_id');
            $table->index('tax_invoice_id');
            $table->index('fulfillment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
