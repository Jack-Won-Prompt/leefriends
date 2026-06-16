<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // hq(본사) | store(매장) | supplier(공급처) | '' (일반/마케팅 관리자)
            $table->string('role')->default('')->after('is_admin');
            $table->unsignedBigInteger('store_id')->nullable()->after('role');
            $table->unsignedBigInteger('supplier_id')->nullable()->after('store_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'store_id', 'supplier_id']);
        });
    }
};
