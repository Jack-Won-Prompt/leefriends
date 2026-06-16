<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 기존 제품코드를 연속 코드(P00001..)로 재채번 (id 순)
        $products = DB::table('supply_products')->orderBy('id')->get();
        $seq = 0;
        foreach ($products as $p) {
            $seq++;
            DB::table('supply_products')->where('id', $p->id)->update([
                'code' => sprintf('P%05d', $seq),
            ]);
        }
    }

    public function down(): void
    {
        // 비가역 (이전 임의 코드 복원 불가)
    }
};
