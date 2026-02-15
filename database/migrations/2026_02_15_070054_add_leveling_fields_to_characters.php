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
            $table->integer('level')->default(1)->after('class');
            $table->bigInteger('experience')->default(0)->after('level');
            $table->integer('stat_points')->default(0)->after('experience');
            
            // Отслеживание вложенных очков для валидации (max 2 на уровень)
            $table->integer('strength_added')->default(0)->after('strength');
            $table->integer('agility_added')->default(0)->after('agility');
            $table->integer('constitution_added')->default(0)->after('constitution');
            $table->integer('intelligence_added')->default(0)->after('intelligence');
            $table->integer('luck_added')->default(0)->after('luck');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn([
                'level', 'experience', 'stat_points',
                'strength_added', 'agility_added', 'constitution_added', 
                'intelligence_added', 'luck_added'
            ]);
        });
    }
};
