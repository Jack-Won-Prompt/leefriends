<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('role', 20)->default('hq')->after('id'); // 소유 역할: hq/store/supplier
            $table->foreignId('store_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->after('store_id')->constrained()->nullOnDelete();
            $table->index(['role', 'store_id', 'supplier_id']);
        });

        // 기존 일정은 본사 소유로 간주
        DB::table('schedules')->whereNull('role')->update(['role' => 'hq']);
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn('role');
        });
    }
};
