<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_product_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supply_product_id');
            $table->string('name');                              // 단위명 (개/박스/kg ...)
            $table->unsignedInteger('supply_price')->default(0); // 단위별 공급가 (공급처→본사)
            $table->unsignedInteger('store_price')->default(0);  // 단위별 출고가 (본사→매장)
            $table->boolean('is_default')->default(false);       // 기본 단위
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('supply_product_id');
        });

        // 기존 제품의 단위/가격을 기본 단위로 백필
        $products = DB::table('supply_products')->get();
        foreach ($products as $p) {
            DB::table('supply_product_units')->insert([
                'supply_product_id' => $p->id,
                'name' => $p->unit ?: '개',
                'supply_price' => $p->supply_price ?? 0,
                'store_price' => $p->store_price ?? 0,
                'is_default' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_product_units');
    }
};
