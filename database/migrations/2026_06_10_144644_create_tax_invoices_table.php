<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 공급처 → 본사 세금계산서
        Schema::create('tax_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();          // 계산서 번호
            $table->unsignedBigInteger('supplier_id');       // 발행 공급처
            $table->unsignedInteger('supply_amount')->default(0); // 공급가액 (공급가 합계)
            $table->unsignedInteger('vat')->default(0);           // 부가세 (10%)
            $table->unsignedInteger('total_amount')->default(0);  // 합계금액
            $table->string('status')->default('issued');     // issued(발행) | canceled(취소)
            $table->date('issue_date');                      // 작성일자
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_invoices');
    }
};
