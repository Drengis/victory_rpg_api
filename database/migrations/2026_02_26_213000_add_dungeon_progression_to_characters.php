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
        Schema::table('characters', function (Blueprint $table) {
            $table->integer('dungeon_depth')->default(1)->after('luck_added');
        });

        Schema::table('character_dynamic_stats', function (Blueprint $table) {
            $table->integer('enemies_defeated_at_depth')->default(0)->after('is_in_combat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('dungeon_depth');
        });

        Schema::table('character_dynamic_stats', function (Blueprint $table) {
            $table->dropColumn('enemies_defeated_at_depth');
        });
    }
};
