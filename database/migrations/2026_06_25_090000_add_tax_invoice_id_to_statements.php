<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            // 거래명세서 기반 세금계산서 발행 추적
            $table->foreignId('tax_invoice_id')->nullable()->after('resend_count')
                ->constrained('tax_invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tax_invoice_id');
        });
    }
};
