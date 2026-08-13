<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 배송완료 증빙(발주 단위): 현장 사진(여러 장)·매장 담당자 서명.
 * 출고지시서 QR = 발주번호(order_no) 기준이므로 발주에 증빙을 붙인다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->json('delivery_photos')->nullable()->after('note');
            $table->string('delivery_signature')->nullable()->after('delivery_photos');
            $table->timestamp('delivered_at')->nullable()->after('delivery_signature');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_photos', 'delivery_signature', 'delivered_at']);
        });
    }
};
