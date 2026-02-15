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
        Schema::create('class_abilities', function (Blueprint $table) {
            $table->id();
            $table->string('class'); // 'воин', 'лучник', 'маг'
            $table->string('ability_name');
            $table->string('ability_type')->default('defense'); // 'defense', 'attack', 'buff'
            $table->integer('mp_cost')->default(0);
            $table->integer('max_uses_per_combat')->nullable(); // NULL = неограниченно
            $table->integer('cooldown_turns')->default(0); // Для будущего
            $table->string('effect_type'); // 'temp_armor', 'temp_evasion', 'barrier'
            $table->string('effect_formula'); // 'constitution', 'intelligence * 1.5'
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['class', 'ability_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_abilities');
    }
};
