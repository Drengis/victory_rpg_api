<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enemy_loot_table', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enemy_id')->constrained()->onDelete('cascade');
            $table->foreignId('loot_table_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['enemy_id', 'loot_table_id']);
        });

        // Мигрируем существующие данные из enemies.loot_table_id в pivot
        $enemies = DB::table('enemies')->whereNotNull('loot_table_id')->get();
        foreach ($enemies as $enemy) {
            DB::table('enemy_loot_table')->insert([
                'enemy_id' => $enemy->id,
                'loot_table_id' => $enemy->loot_table_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Убираем старую колонку
        Schema::table('enemies', function (Blueprint $table) {
            $table->dropForeign(['loot_table_id']);
            $table->dropColumn('loot_table_id');
        });
    }

    public function down(): void
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->foreignId('loot_table_id')->nullable()->constrained()->onDelete('set null');
        });

        // Восстанавливаем данные
        $pivots = DB::table('enemy_loot_table')->get();
        foreach ($pivots as $pivot) {
            DB::table('enemies')->where('id', $pivot->enemy_id)->update([
                'loot_table_id' => $pivot->loot_table_id,
            ]);
        }

        Schema::dropIfExists('enemy_loot_table');
    }
};
