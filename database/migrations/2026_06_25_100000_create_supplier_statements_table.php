<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_name');
            $table->string('statement_no')->unique();
            $table->unsignedInteger('item_count')->default(0);
            $table->unsignedBigInteger('supply_total')->default(0);
            $table->unsignedBigInteger('vat')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->json('items');
            $table->foreignId('tax_invoice_id')->nullable()->constrained('tax_invoices')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 거래명세서에 묶인 주문 품목 추적(중복 포함 방지)
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('supplier_statement_id')->nullable()->after('tax_invoice_id')
                ->constrained('supplier_statements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_statement_id');
        });
        Schema::dropIfExists('supplier_statements');
    }
};
