<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_invoices', function (Blueprint $table) {
            // 발행 제공자: internal(내부) | popbill(추후 연동)
            $table->string('provider')->default('internal')->after('status');
            // 팝빌 발행 시 국세청 승인번호 / 문서관리번호
            $table->string('nts_confirm_num')->nullable()->after('provider');
            $table->string('popbill_mgt_key')->nullable()->after('nts_confirm_num');
        });
    }

    public function down(): void
    {
        Schema::table('tax_invoices', function (Blueprint $table) {
            $table->dropColumn(['provider', 'nts_confirm_num', 'popbill_mgt_key']);
        });
    }
};
