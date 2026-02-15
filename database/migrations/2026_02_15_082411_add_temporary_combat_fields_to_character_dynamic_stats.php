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
        Schema::table('character_dynamic_stats', function (Blueprint $table) {
            $table->integer('temp_armor')->default(0);
            $table->integer('temp_evasion')->default(0);
            $table->integer('barrier_hp')->default(0);
            $table->text('last_combat_log')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_dynamic_stats', function (Blueprint $table) {
            $table->dropColumn(['temp_armor', 'temp_evasion', 'barrier_hp', 'last_combat_log']);
        });
    }
};
