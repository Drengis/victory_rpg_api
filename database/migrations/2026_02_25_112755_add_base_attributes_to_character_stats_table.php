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
        Schema::table('character_stats', function (Blueprint $table) {
            $table->integer('strength')->default(5)->after('character_id');
            $table->integer('agility')->default(5)->after('strength');
            $table->integer('constitution')->default(5)->after('agility');
            $table->integer('intelligence')->default(5)->after('constitution');
            $table->integer('luck')->default(5)->after('intelligence');
        });
    }

    public function down(): void
    {
        Schema::table('character_stats', function (Blueprint $table) {
            $table->dropColumn(['strength', 'agility', 'constitution', 'intelligence', 'luck']);
        });
    }
};
