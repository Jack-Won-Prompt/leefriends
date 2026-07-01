<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 직원 고용 형태 — 정직원(regular)/아르바이트(part_time) + 아르바이트 시급.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employment_type', 20)->default('regular')->after('role'); // regular | part_time
            $table->unsignedInteger('hourly_wage')->nullable()->after('employment_type'); // 아르바이트 시급(원)
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['employment_type', 'hourly_wage']);
        });
    }
};
