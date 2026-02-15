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
        Schema::create('combat_ability_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combat_id')->constrained()->onDelete('cascade');
            $table->foreignId('ability_id')->references('id')->on('class_abilities')->onDelete('cascade');
            $table->integer('turn_used');
            $table->timestamps();
            
            $table->index(['combat_id', 'ability_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combat_ability_usage');
    }
};
