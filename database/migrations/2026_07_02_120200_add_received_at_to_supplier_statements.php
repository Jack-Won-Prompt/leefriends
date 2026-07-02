<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 공급처 거래명세서 입고 처리 상태.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_statements', function (Blueprint $table) {
            $table->timestamp('received_at')->nullable()->after('tax_invoice_id');
            $table->foreignId('received_by')->nullable()->after('received_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_statements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('received_by');
            $table->dropColumn('received_at');
        });
    }
};
