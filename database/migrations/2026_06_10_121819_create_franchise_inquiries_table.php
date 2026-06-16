<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('franchise_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('region')->nullable();
            $table->string('budget')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new'); // new, contacted, done
            $table->boolean('agree_privacy')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('franchise_inquiries');
    }
};
