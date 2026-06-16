<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 매장 재고 (제품·단위별 보유 수량)
        Schema::create('store_inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('supply_product_id');
            $table->unsignedBigInteger('supply_product_unit_id')->nullable();
            $table->string('product_name');
            $table->string('unit_name')->default('개');
            $table->integer('qty')->default(0);          // 현재 보유 수량
            $table->timestamps();

            $table->unique(['store_id', 'supply_product_id', 'supply_product_unit_id'], 'store_inv_unique');
            $table->index('store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_inventories');
    }
};
