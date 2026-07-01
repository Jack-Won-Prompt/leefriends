<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 홈택스 전자세금계산서 수집 작업(Job) 이력.
 * 팝빌 HTTaxinvoice::RequestJob 으로 발급받은 jobID 와 수집 상태를 보관해
 * 반복 조회 시 재요청 없이 결과를 재사용한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hometax_collect_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('corp_num', 10);                 // 조회 대상(본사) 사업자번호
            $table->string('ti_type', 10);                  // SELL(매출) / BUY(매입)
            $table->string('date_type', 3)->default('W');   // W:작성일자 I:발행일자 S:전송일자
            $table->string('start_date', 8);                // YYYYMMDD
            $table->string('end_date', 8);
            $table->string('job_id', 18)->nullable()->unique();
            $table->unsignedTinyInteger('job_state')->nullable(); // 1:대기 2:진행 3:완료
            $table->unsignedInteger('collect_count')->nullable();
            $table->integer('error_code')->nullable();
            $table->string('error_reason')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['corp_num', 'ti_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hometax_collect_jobs');
    }
};
