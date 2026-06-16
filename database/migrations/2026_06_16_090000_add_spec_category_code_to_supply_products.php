<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            $table->string('category_code', 20)->nullable()->after('category'); // 대분류코드 (MAC/COO/MAT)
            $table->string('spec')->nullable()->after('unit');                  // 규격 (10개입 / 1.68L ...)
            $table->index('category_code');
        });
    }

    public function down(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            $table->dropIndex(['category_code']);
            $table->dropColumn(['category_code', 'spec']);
        });
    }
};
