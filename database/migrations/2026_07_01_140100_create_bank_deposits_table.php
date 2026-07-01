<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 계좌 입금 거래 내역 (수집분) + 주문 대사 상태.
 * 팝빌 Search 결과를 tid 기준으로 적재하고, 매장 주문과 매칭한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_deposits', function (Blueprint $table) {
            $table->id();
            $table->string('corp_num', 10);
            $table->string('bank_code', 8);
            $table->string('account_number', 40);
            $table->string('tid', 60)->unique();          // 거래 고유 ID
            $table->string('trade_date', 8);               // 거래일자 YYYYMMDD
            $table->string('trade_dt', 20)->nullable();    // 거래일시
            $table->unsignedBigInteger('acc_in')->default(0);   // 입금액
            $table->unsignedBigInteger('acc_out')->default(0);  // 출금액
            $table->bigInteger('balance')->nullable();     // 거래후 잔액
            $table->string('depositor')->nullable();       // 입금자명(적요)
            $table->string('remark')->nullable();          // 기타 적요
            $table->string('memo')->nullable();            // 사용자 메모
            $table->foreignId('matched_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable(); // 입금확인 확정 시각
            $table->timestamps();

            $table->index(['corp_num', 'trade_date']);
            $table->index('matched_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_deposits');
    }
};
