<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 매장 거래 원장 (예치금 잔액 + 미수금 타임라인)
        Schema::create('store_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('type');            // charge충전 / order발주차감 / adjust조정 / refund환불
            $table->integer('amount');         // +충전 / -차감
            $table->integer('balance_after');  // 처리 후 잔액(누적)
            $table->string('source')->nullable(); // deposit / order / manual
            $table->string('ref_type')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('memo')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['store_id', 'created_at']);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->string('settlement_type')->default('postpaid')->after('email'); // prepaid선입금 / postpaid후불
            $table->string('virtual_account')->nullable()->after('settlement_type'); // 전용 입금 식별(가상계좌/입금코드)
            $table->integer('ledger_balance')->default(0)->after('virtual_account');  // 현재 잔액(+예치 / -미수)
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['settlement_type', 'virtual_account', 'ledger_balance']);
        });
        Schema::dropIfExists('store_ledger_entries');
    }
};
