<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // 배송 주소 (입고지) - 기존 address 를 배송 기본주소로 사용
            $table->string('postcode')->nullable()->after('address');          // 배송 우편번호
            $table->string('address_detail')->nullable()->after('postcode');   // 배송 상세주소
            // 법인 주소
            $table->string('corp_postcode')->nullable()->after('address_detail');
            $table->string('corp_address')->nullable()->after('corp_postcode');
            $table->string('corp_address_detail')->nullable()->after('corp_address');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['postcode', 'address_detail', 'corp_postcode', 'corp_address', 'corp_address_detail']);
        });
    }
};
