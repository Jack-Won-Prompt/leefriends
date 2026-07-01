<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 아르바이트 급여 입금 처리 — 기간별 급여 정산/입금 상태.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wage_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('period_from');
            $table->date('period_to');
            $table->decimal('total_hours', 8, 2)->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('status', 20)->default('paid'); // paid
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'period_from', 'period_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wage_settlements');
    }
};
