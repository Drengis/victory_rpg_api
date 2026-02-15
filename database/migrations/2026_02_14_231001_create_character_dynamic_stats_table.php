<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_dynamic_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->onDelete('cascade');
            
            // Текущие показатели
            $table->float('current_hp')->default(100);
            $table->float('current_mp')->default(50);
            
            // Время последней регенерации
            $table->timestamp('last_regen_at')->useCurrent();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_dynamic_stats');
    }
};
