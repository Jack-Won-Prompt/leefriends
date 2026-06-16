<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 매장 주문 수정/삭제 이벤트 (판매자별 미반영 추적)
        Schema::create('order_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('store_id');
            $table->string('change_type');               // updated | canceled
            $table->string('seller_type');               // hq | supplier
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('order_no')->nullable();
            $table->string('summary')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamps();

            $table->index(['seller_type', 'supplier_id', 'acknowledged_at'], 'oc_seller_ack_idx');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_changes');
    }
};
