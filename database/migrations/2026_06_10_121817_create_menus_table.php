<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('category')->default('bingsu'); // signature, bingsu, drink, dessert
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->string('image')->nullable();
            $table->string('badge')->nullable(); // best, new, hot
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
