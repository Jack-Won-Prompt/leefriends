<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 거래명세서 매장 수취 상태 — 열람/확인.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            $table->timestamp('viewed_at')->nullable()->after('sent_at');       // 매장 최초 열람
            $table->timestamp('confirmed_at')->nullable()->after('viewed_at');  // 매장 확인
            $table->foreignId('confirmed_by')->nullable()->after('confirmed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn(['viewed_at', 'confirmed_at']);
        });
    }
};
