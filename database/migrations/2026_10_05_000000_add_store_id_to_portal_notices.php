<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 포털 공지 단일 매장 타겟팅.
 * store_id NULL = audience 전체(기존 동작), 값 있으면 해당 매장에만 발송/노출.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_notices', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('audience')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('portal_notices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
        });
    }
};
