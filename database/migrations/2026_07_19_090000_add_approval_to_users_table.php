<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // approved(승인/기존계정) | pending(본사 승인 대기) | rejected(반려)
            // 기본값을 approved로 두어 기존 계정·본사 초대 계정은 그대로 이용 가능
            $table->string('approval_status')->default('approved')->after('supplier_id')->index();
            $table->timestamp('approved_at')->nullable()->after('approval_status');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            $table->string('rejected_reason')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'approved_at', 'approved_by', 'rejected_reason']);
        });
    }
};
