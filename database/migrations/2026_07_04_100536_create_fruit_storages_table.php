<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 과일·채소 냉장/냉동 보관 가이드라인 (ZIM 권장 가이드 기반)
        Schema::create('fruit_storages', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // 제품
            $table->string('temp_c')->nullable();         // 온도 °C
            $table->string('temp_f')->nullable();         // 온도 °F
            $table->string('ventilation')->nullable();    // 통기공 구성 CMH
            $table->string('humidity')->nullable();       // 상대 습도 (%)
            $table->string('dehumidification')->nullable(); // 제습 (끔/켬)
            $table->string('storage_period')->nullable(); // 적절 보관 기한
            $table->string('note')->nullable();           // 비고
            $table->boolean('is_shared')->default(false); // 매장 공유
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fruit_storages');
    }
};
