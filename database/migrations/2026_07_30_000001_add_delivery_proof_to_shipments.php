<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 배송완료 증빙: 현장 사진(여러 장)·매장 담당자 서명.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->json('delivery_photos')->nullable()->after('note');        // 현장 사진 경로 배열
            $table->string('delivery_signature')->nullable()->after('delivery_photos'); // 서명 이미지 경로
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['delivery_photos', 'delivery_signature']);
        });
    }
};
