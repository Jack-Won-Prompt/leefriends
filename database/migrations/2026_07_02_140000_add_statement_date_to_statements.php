<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 거래명세서 발행일자 (미지정 시 작성일 사용).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            $table->date('statement_date')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            $table->dropColumn('statement_date');
        });
    }
};
