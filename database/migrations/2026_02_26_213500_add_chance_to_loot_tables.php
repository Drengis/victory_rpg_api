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
        Schema::table('loot_tables', function (Blueprint $table) {
            $table->float('chance')->default(100.0)->after('mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loot_tables', function (Blueprint $table) {
            $table->dropColumn('chance');
        });
    }
};
