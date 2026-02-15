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
        Schema::create('combat_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enemy_id')->constrained()->cascadeOnDelete();
            $table->integer('current_hp');
            $table->integer('current_mp');
            $table->integer('level'); // Снапшот уровня моба
            $table->integer('position')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combat_participants');
    }
};
