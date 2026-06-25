<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_statements', function (Blueprint $table) {
            $table->timestamp('emailed_at')->nullable()->after('items');
            $table->unsignedInteger('email_count')->default(0)->after('emailed_at');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_statements', function (Blueprint $table) {
            $table->dropColumn(['emailed_at', 'email_count']);
        });
    }
};
