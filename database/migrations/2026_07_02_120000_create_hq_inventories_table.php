<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 본사(창고) 재고 — 품목별 실물재고(qty) / 출고예정 예약(reserved_qty).
 * 가용재고 = qty - reserved_qty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hq_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_product_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('product_name');            // 표시용 denorm
            $table->integer('qty')->default(0);        // 실물 재고
            $table->integer('reserved_qty')->default(0); // 출고 예정(발주 예약)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hq_inventories');
    }
};
