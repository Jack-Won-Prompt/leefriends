<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 입금자명 ↔ 매장 매핑.
 * 계좌 입금거래의 입금자명을 매장에 한 번 매핑해 두면,
 * 이후 같은 입금자명은 자동으로 해당 매장으로 인식한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_depositor_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('corp_num', 10);
            $table->string('depositor_name');
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['corp_num', 'depositor_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_depositor_mappings');
    }
};
