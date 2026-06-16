<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 출고: 본사/공급처가 매장별로 묶어 생성
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_no')->unique();           // 출고번호(=거래명세서 바코드 값)
            $table->string('seller_type')->default('hq');      // hq | supplier
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('store_id');            // 배송지 매장

            // created(작성) | confirmed(출고확정/배송시작) | received(입고완료) | canceled
            $table->string('status')->default('created');

            $table->string('carrier')->nullable();             // 택배사
            $table->string('tracking_no')->nullable();         // 송장번호
            $table->unsignedInteger('item_count')->default(0);
            $table->unsignedInteger('total_qty')->default(0);
            $table->unsignedInteger('supply_amount')->default(0);
            $table->text('note')->nullable();

            $table->timestamp('confirmed_at')->nullable();     // 출고확정 시각
            $table->timestamp('received_at')->nullable();      // 입고완료 시각
            $table->unsignedBigInteger('received_by')->nullable(); // 인수 처리자(매장 user)
            $table->timestamps();

            $table->index(['seller_type', 'supplier_id']);
            $table->index('store_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
