<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loot_tables', function (Blueprint $table) {
            // 'each' — каждый предмет ролится независимо (свой % шанса)
            // 'one'  — ролится один предмет из таблицы (chance используется как вес)
            $table->enum('mode', ['each', 'one'])->default('each')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('loot_tables', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
};
