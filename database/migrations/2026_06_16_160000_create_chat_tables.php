<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 본사 ↔ 매장/공급처 1:1 대화방 (각 매장/공급처마다 본사와 1개)
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->enum('party_type', ['store', 'supplier']); // 본사의 상대방 유형
            $table->unsignedBigInteger('party_id');            // store_id 또는 supplier_id
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message')->nullable();
            $table->unsignedInteger('hq_unread')->default(0);     // 본사 미읽음 수
            $table->unsignedInteger('party_unread')->default(0);  // 매장/공급처 미읽음 수
            $table->timestamps();
            $table->unique(['party_type', 'party_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('sender_role', ['hq', 'store', 'supplier']);
            $table->string('sender_name')->nullable();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
