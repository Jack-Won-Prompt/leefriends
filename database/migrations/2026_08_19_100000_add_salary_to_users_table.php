<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 정직원 급여 관리용 컬럼.
 * - monthly_salary : 월 정해진 급여(원)
 * - work_start/work_end : 표준 근무시간(정시 기준 → 지각·오버타임 산정)
 * - standard_workdays : 월 표준 근무일수(시급 환산 분모)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('monthly_salary')->nullable()->after('hourly_wage');
            $table->string('work_start', 5)->nullable()->after('monthly_salary');   // 'HH:MM'
            $table->string('work_end', 5)->nullable()->after('work_start');         // 'HH:MM'
            $table->unsignedSmallInteger('standard_workdays')->nullable()->after('work_end');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['monthly_salary', 'work_start', 'work_end', 'standard_workdays']);
        });
    }
};
