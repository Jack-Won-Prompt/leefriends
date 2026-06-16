<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FCM 디바이스 토큰 (모바일 앱이 등록)
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token', 512);
            $table->string('platform')->nullable();   // android | ios | web
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique('token');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
