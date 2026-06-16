<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();          // 품목코드
            $table->string('name');                       // 품목명(재료)
            $table->string('category')->default('기타');   // 분류 (원물/부자재/포장재 등)
            $table->string('unit')->default('개');         // 단위 (박스/kg/개 ...)

            // 공급유형: hq(본사 직공급) | supplier(공급처 직배송)
            $table->string('supply_type')->default('hq');
            $table->unsignedBigInteger('supplier_id')->nullable(); // supplier 유형일 때 담당 공급처

            $table->unsignedInteger('supply_price')->default(0); // 공급가 (공급처 → 본사)
            $table->unsignedInteger('store_price')->default(0);  // 출고가 (본사 → 매장)

            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('supplier_id');
            $table->index('supply_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_products');
    }
};
