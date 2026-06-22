<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 거래명세서 발송 이력 (스냅샷)
        Schema::create('statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->string('store_name');         // 발송 시점 매장명 스냅샷
            $table->string('email')->nullable();  // 수신 이메일
            $table->unsignedInteger('item_count')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->json('items');                // [{code,name,unit,qty,price,amount}]
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at');
            $table->unsignedInteger('resend_count')->default(0);
            $table->timestamps();
            $table->index(['store_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statements');
    }
};
