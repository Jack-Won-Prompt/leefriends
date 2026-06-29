<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('statement_emailed_at')->nullable()->after('shipping_fee'); // 거래명세서 매장 전송 시각
            $table->unsignedInteger('statement_email_count')->default(0)->after('statement_emailed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['statement_emailed_at', 'statement_email_count']);
        });
    }
};
