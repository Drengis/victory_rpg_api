<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->integer('base_armor')->default(0)->after('max_damage');
        });
    }

    public function down(): void
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->dropColumn('base_armor');
        });
    }
};
