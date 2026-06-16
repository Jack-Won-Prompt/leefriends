<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('code');
            $table->index('barcode');
        });

        // 기존 제품에 바코드 백필 (코드 기반, 없으면 LF + id)
        foreach (DB::table('supply_products')->get() as $p) {
            DB::table('supply_products')->where('id', $p->id)->update([
                'barcode' => $p->barcode ?: ('LF' . str_pad((string) $p->id, 8, '0', STR_PAD_LEFT)),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });
    }
};
