<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_direct')->default(false); // 직접 배송(송장번호 불필요)
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 기본 택배사 + 직접 배송
        $now = now();
        DB::table('couriers')->insert([
            ['name' => '직접 배송', 'is_direct' => true, 'is_active' => true, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'CJ대한통운', 'is_direct' => false, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => '한진택배', 'is_direct' => false, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => '롯데택배', 'is_direct' => false, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['name' => '우체국택배', 'is_direct' => false, 'is_active' => true, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['name' => '로젠택배', 'is_direct' => false, 'is_active' => true, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};
