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
            $table->unsignedTinyInteger('str')->default(10); // Strength
            $table->unsignedTinyInteger('dex')->default(10); // Dexterity
            $table->unsignedTinyInteger('con')->default(10); // Constitution
            $table->unsignedTinyInteger('int')->default(10); // Intelligence
            $table->unsignedTinyInteger('wis')->default(10); // Wisdom
            $table->unsignedTinyInteger('cha')->default(10); // Charisma
            $table->unsignedInteger('hp')->default(10);      // Hit points
            $table->unsignedInteger('max_hp')->default(10);  // Max HP
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn(['str', 'dex', 'con', 'int', 'wis', 'cha', 'hp', 'max_hp']);
        });
    }
};
