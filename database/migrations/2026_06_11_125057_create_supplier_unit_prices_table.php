<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 공급처 × 단위 공급가 (한 제품을 여러 공급처가 공급)
        Schema::create('supplier_unit_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supply_product_id');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('supply_product_unit_id');
            $table->unsignedInteger('supply_price')->default(0);
            $table->timestamps();

            $table->unique(['supply_product_id', 'supplier_id', 'supply_product_unit_id'], 'sup_unit_price_unique');
            $table->index('supply_product_id');
            $table->index('supplier_id');
        });

        // 기존 데이터 백필: supplier 유형 제품의 기존 단위 공급가를 기본 공급처(supplier_id) 가격으로
        $rows = DB::table('supply_products')
            ->where('supply_type', 'supplier')
            ->whereNotNull('supplier_id')
            ->get();
        foreach ($rows as $p) {
            $units = DB::table('supply_product_units')->where('supply_product_id', $p->id)->get();
            foreach ($units as $u) {
                DB::table('supplier_unit_prices')->insert([
                    'supply_product_id' => $p->id,
                    'supplier_id' => $p->supplier_id,
                    'supply_product_unit_id' => $u->id,
                    'supply_price' => $u->supply_price ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_unit_prices');
    }
};
