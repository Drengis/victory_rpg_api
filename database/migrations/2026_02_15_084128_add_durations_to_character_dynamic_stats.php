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
            $table->integer('temp_armor_duration')->default(0)->after('temp_armor');
            $table->integer('temp_evasion_duration')->default(0)->after('temp_evasion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_dynamic_stats', function (Blueprint $table) {
            $table->dropColumn(['temp_armor_duration', 'temp_evasion_duration']);
        });
    }
};
