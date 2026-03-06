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
            $table->json('effects')->nullable();
        });

        Schema::table('combat_participants', function (Blueprint $table) {
            $table->json('effects')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_dynamic_stats', function (Blueprint $table) {
            $table->dropColumn('effects');
        });

        Schema::table('combat_participants', function (Blueprint $table) {
            $table->dropColumn('effects');
        });
    }
};
