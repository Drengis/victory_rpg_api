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
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('class'); // воин, лучник, маг
            
            // Базовые характеристики
            $table->integer('strength')->default(10);
            $table->integer('agility')->default(10);
            $table->integer('constitution')->default(10);
            $table->integer('intelligence')->default(10);
            $table->integer('luck')->default(10);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
