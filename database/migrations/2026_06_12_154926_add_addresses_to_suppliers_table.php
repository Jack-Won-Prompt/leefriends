<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // 법인 주소 - 기존 address 를 법인 기본주소로 사용
            $table->string('postcode')->nullable()->after('address');          // 법인 우편번호
            $table->string('address_detail')->nullable()->after('postcode');   // 법인 상세주소
            // 반품 주소 (매장 반품 시 보낼 곳)
            $table->string('return_postcode')->nullable()->after('address_detail');
            $table->string('return_address')->nullable()->after('return_postcode');
            $table->string('return_address_detail')->nullable()->after('return_address');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['postcode', 'address_detail', 'return_postcode', 'return_address', 'return_address_detail']);
        });
    }
};
