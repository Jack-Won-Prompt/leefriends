<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 레시피 — 본사가 물품별로 이미지+글로 올리고, 매장이 확인.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name')->nullable(); // 물품 삭제돼도 표기 유지
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
