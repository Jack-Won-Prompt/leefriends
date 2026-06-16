<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 재고 이동 내역 (입고/출고/조정)
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('supply_product_id');
            $table->unsignedBigInteger('supply_product_unit_id')->nullable();
            $table->string('product_name');
            $table->string('unit_name')->default('개');

            $table->string('type');                       // in(입고) | out(출고/사용) | adjust(조정)
            $table->string('source')->nullable();         // inbound | usage | adjust
            $table->integer('qty');                       // 변동 수량(+/-)
            $table->integer('balance_after')->default(0); // 변동 후 잔량
            $table->unsignedBigInteger('shipment_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('store_id');
            $table->index(['supply_product_id', 'supply_product_unit_id'], 'inv_mov_product_idx');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
