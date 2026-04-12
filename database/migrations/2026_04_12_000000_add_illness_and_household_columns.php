<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sim_npcs', function (Blueprint $table) {
            // Illness: null = healthy, string = illness type
            $table->string('illness')->nullable()->after('starving_since_tick');
            $table->integer('illness_since_tick')->nullable()->after('illness');

            // Household: NPCs in the same household share food and shelter
            $table->unsignedBigInteger('household_id')->nullable()->after('illness_since_tick');

            // Goal system: what this NPC is currently working toward
            $table->string('goal_type')->nullable()->after('household_id');
            $table->integer('goal_target')->default(0)->after('goal_type');
            $table->integer('goal_progress')->default(0)->after('goal_target');
        });

        // Household table — just an ID and a home place
        Schema::create('sim_households', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('home_place_id')->nullable()->constrained('sim_places')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('sim_npcs', function (Blueprint $table) {
            $table->dropColumn(['illness', 'illness_since_tick', 'household_id', 'goal_type', 'goal_target', 'goal_progress']);
        });
        Schema::dropIfExists('sim_households');
    }
};
