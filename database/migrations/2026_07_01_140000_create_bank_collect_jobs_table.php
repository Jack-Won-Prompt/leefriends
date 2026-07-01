<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 계좌조회(EasyFinBank) 수집 작업 이력.
 * 팝빌 RequestJob 으로 발급받은 jobID·수집 상태 보관.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_collect_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('corp_num', 10);
            $table->string('bank_code', 8);
            $table->string('account_number', 40);
            $table->string('start_date', 8);
            $table->string('end_date', 8);
            $table->string('job_id', 18)->nullable()->unique();
            $table->unsignedTinyInteger('job_state')->nullable(); // 1:대기 2:진행 3:완료
            $table->unsignedInteger('collect_count')->nullable();
            $table->integer('error_code')->nullable();
            $table->string('error_reason')->nullable();
            $table->timestamp('imported_at')->nullable(); // 입금내역 로컬 반영 시각
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['corp_num', 'bank_code', 'account_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_collect_jobs');
    }
};
