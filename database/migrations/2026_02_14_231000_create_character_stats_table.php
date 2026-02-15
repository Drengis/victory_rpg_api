<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->onDelete('cascade');
            
            // "Потолки" и коэффициенты
            $table->integer('max_hp')->default(100);
            $table->float('hp_regen')->default(1.0);
            $table->integer('max_mp')->default(50);
            $table->float('mp_regen')->default(0.5);
            
            $table->float('physical_damage_bonus')->default(0);
            $table->float('magical_damage_bonus')->default(0);
            $table->float('accuracy')->default(0);
            $table->float('evasion')->default(0);
            $table->float('crit_chance')->default(0);
            $table->float('rare_loot_bonus')->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_stats');
    }
};
