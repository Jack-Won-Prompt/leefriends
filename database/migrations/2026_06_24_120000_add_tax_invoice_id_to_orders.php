<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 본사→매장 세금계산서 발행 추적 (발행된 발주는 재발행 대상에서 제외)
            $table->foreignId('tax_invoice_id')->nullable()->after('note')
                ->constrained('tax_invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tax_invoice_id');
        });
    }
};
