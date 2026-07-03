<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // 배송완료(delivered) 시각 — confirmed(배송중)와 received(입고완료) 사이 본사 처리
            $table->timestamp('delivered_at')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('delivered_at');
        });
    }
};
