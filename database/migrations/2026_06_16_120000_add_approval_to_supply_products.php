<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            // 승인 상태: approved(노출) | pending(승인대기) | rejected(반려)
            $table->string('approval_status', 20)->default('approved')->after('is_active');
            // 등록 주체: hq | supplier
            $table->string('registered_by', 20)->default('hq')->after('approval_status');
            $table->string('approval_note')->nullable()->after('registered_by'); // 반려 사유 등
            $table->index('approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            $table->dropIndex(['approval_status']);
            $table->dropColumn(['approval_status', 'registered_by', 'approval_note']);
        });
    }
};
