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
            $table->bigInteger('gold')->default(0)->after('experience');
        });

        Schema::table('enemies', function (Blueprint $table) {
            $table->integer('base_experience')->default(20)->after('max_damage');
            $table->integer('base_gold')->default(10)->after('base_experience');
            $table->unsignedBigInteger('loot_table_id')->nullable()->after('base_gold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('gold');
        });

        Schema::table('enemies', function (Blueprint $table) {
            $table->dropColumn(['base_experience', 'base_gold', 'loot_table_id']);
        });
    }
};
