<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 메인 페이지 팝업(관리자 설정) — 프랜차이즈·매장·카페 과일 문의 등.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('popups', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(false);      // 메인 노출 여부
            $table->string('title')->default('과일 납품 문의');
            $table->text('body')->nullable();                  // 안내 문구
            $table->string('image')->nullable();               // 팝업 이미지 경로
            $table->string('contact')->nullable();             // 문의 연락처(전화 등)
            $table->string('link_url')->default('/franchise#inquiry'); // 클릭 시 이동
            $table->string('link_label')->default('문의하기');  // 버튼 문구
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('popups');
    }
};
