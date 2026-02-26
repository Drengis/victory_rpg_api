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
            $table->integer('level_required')->default(1)->after('class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_abilities', function (Blueprint $table) {
            $table->dropColumn('level_required');
        });
    }
};
