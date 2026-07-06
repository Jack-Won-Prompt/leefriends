<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 공개 사이트 방문 로그 (본사 방문 분석용). IP는 해시로 익명 저장.
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->string('path')->index();          // 요청 경로 (/menu 등)
            $table->string('page_name')->nullable();   // 페이지 라벨 (홈/메뉴/창업…)
            $table->string('source')->default('direct')->index(); // 유입 경로 (direct/naver/google…)
            $table->string('referrer')->nullable();    // 유입 도메인
            $table->string('device', 20)->default('desktop'); // mobile/desktop
            $table->string('visitor_hash', 64)->index(); // 방문자 식별(세션 해시)
            $table->string('ip_hash', 64)->nullable(); // IP 해시(익명)
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};
