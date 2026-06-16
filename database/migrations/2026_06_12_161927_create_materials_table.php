<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 재료 마스터 (품목과 별개). type: extra(추가 품목 재료) | etc(기타 재료)
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('extra');   // extra | etc
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->default('개');
            $table->string('spec')->nullable();          // 규격
            $table->text('note')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
