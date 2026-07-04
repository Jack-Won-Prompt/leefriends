<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 블로그 글 (공식 네이버 블로그 RSS 자동수집)
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique(); // 블로그 글 고유키(로그번호 등)
            $table->string('title');
            $table->string('url', 500);
            $table->string('thumbnail', 500)->nullable();
            $table->string('summary', 500)->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 네이버 클립 (관리자 수동 등록)
        Schema::create('naver_clips', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('url', 500);
            $table->string('thumbnail', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('naver_clips');
        Schema::dropIfExists('blog_posts');
    }
};
