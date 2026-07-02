<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 본사 재고 이동 이력 — 입고/예약/해제/출고/조정.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hq_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_product_id')->constrained()->cascadeOnDelete();
            $table->string('product_name');
            $table->string('type', 20);            // inbound | reserve | release | ship | adjust
            $table->integer('qty_delta')->default(0);       // 실물 재고 변화(+/-)
            $table->integer('reserved_delta')->default(0);  // 예약 변화(+/-)
            $table->integer('balance_qty');        // 처리 후 실물
            $table->integer('balance_reserved');   // 처리 후 예약
            $table->string('source', 20)->nullable();   // statement | manual | order | shipment
            $table->string('ref_type', 40)->nullable(); // 참조 모델
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['supply_product_id', 'created_at']);
            $table->index(['ref_type', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hq_inventory_movements');
    }
};
