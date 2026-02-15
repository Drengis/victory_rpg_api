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
        Schema::create('enemies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('level')->default(1);
            
            // Базовые характеристики
            $table->integer('strength')->default(5);
            $table->integer('agility')->default(5);
            $table->integer('constitution')->default(5);
            $table->integer('intelligence')->default(5);
            $table->integer('luck')->default(5);
            
            // Базовый урон (может быть перекрыт характеристиками)
            $table->integer('min_damage')->default(1);
            $table->integer('max_damage')->default(2);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enemies');
    }
};
