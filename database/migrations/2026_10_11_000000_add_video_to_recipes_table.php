<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 레시피 영상 — 본사가 이미지와 별개로 영상을 올리고, 매장이 함께 본다.
 * - video      : 업로드한 영상 파일 경로 (public/videos/recipes/...)
 * - video_link : 외부 영상 링크(유튜브 등). 서버 업로드 한도를 넘는 큰 영상용.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->string('video')->nullable()->after('image');
            $table->string('video_link')->nullable()->after('video');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['video', 'video_link']);
        });
    }
};
