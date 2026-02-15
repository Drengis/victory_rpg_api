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
        Schema::create('loot_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('loot_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loot_table_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->float('chance'); // 0-100
            $table->integer('min_quantity')->default(1);
            $table->integer('max_quantity')->default(1);
            $table->timestamps();
        });

        Schema::table('enemies', function (Blueprint $table) {
            $table->foreign('loot_table_id')->references('id')->on('loot_tables')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->dropForeign(['loot_table_id']);
        });

        Schema::dropIfExists('loot_items');
        Schema::dropIfExists('loot_tables');
    }
};
