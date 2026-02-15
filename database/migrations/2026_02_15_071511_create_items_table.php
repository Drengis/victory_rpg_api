<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // weapon, head, chest, etc.
            $table->integer('quality')->default(1); // 1-5 (common, uncommon, etc.)
            
            // Базовые бонусы (чистые, без учета редкости в БД, редкость применим в коде)
            $table->integer('strength')->default(0);
            $table->integer('agility')->default(0);
            $table->integer('constitution')->default(0);
            $table->integer('intelligence')->default(0);
            $table->integer('luck')->default(0);
            
            // Урон для оружия
            $table->integer('min_damage')->default(0);
            $table->integer('max_damage')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
