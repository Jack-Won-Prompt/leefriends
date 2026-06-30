<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->date('schedule_date');
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('color', 20)->default('mango');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('schedule_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
