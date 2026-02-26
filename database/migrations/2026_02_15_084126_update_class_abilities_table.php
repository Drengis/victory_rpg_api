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
        Schema::table('class_abilities', function (Blueprint $table) {
            $table->integer('gold_cost')->default(0)->after('mp_cost');
            $table->integer('duration')->default(1)->after('cooldown_turns');
            $table->integer('level_required')->default(1)->change(); // Убедимся, что поле есть и имеет дефолт
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_abilities', function (Blueprint $table) {
            $table->dropColumn(['gold_cost', 'duration']);
        });
    }
};
