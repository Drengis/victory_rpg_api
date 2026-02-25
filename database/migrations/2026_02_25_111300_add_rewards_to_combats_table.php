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
        Schema::table('combats', function (Blueprint $table) {
            $table->integer('gold_reward')->default(0);
            $table->integer('experience_reward')->default(0);
            $table->json('loot_reward')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('combats', function (Blueprint $table) {
            $table->dropColumn(['gold_reward', 'experience_reward', 'loot_reward']);
        });
    }
};
