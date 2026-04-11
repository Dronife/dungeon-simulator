<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sim_places', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('subtype');
            $table->string('scale')->default('medium');
            $table->string('condition')->default('kept');
            $table->string('climate')->default('temperate');
            $table->string('terrain')->default('dirt');
            $table->string('weather')->default('clear');
            $table->string('time_of_day')->default('noon');
            $table->integer('x');
            $table->integer('y');
            $table->integer('width')->default(2);
            $table->integer('height')->default(2);
            $table->integer('danger_level')->default(0);
            $table->integer('population')->default(0);
            $table->integer('prosperity')->default(5);
            $table->foreignId('parent_id')->nullable()->constrained('sim_places')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sim_npcs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('race');
            $table->string('gender');
            $table->integer('age');
            $table->string('build');
            $table->string('profession');
            $table->string('social_class');
            $table->integer('wealth')->default(0);

            // OCEAN 1-10
            $table->tinyInteger('openness');
            $table->tinyInteger('conscientiousness');
            $table->tinyInteger('extraversion');
            $table->tinyInteger('agreeableness');
            $table->tinyInteger('neuroticism');

            // physical
            $table->tinyInteger('str');
            $table->tinyInteger('dex');
            $table->tinyInteger('con');
            $table->tinyInteger('int');
            $table->integer('hp');
            $table->integer('max_hp');

            // needs 0-100 — lower = more urgent
            $table->tinyInteger('hunger')->default(80);
            $table->tinyInteger('thirst')->default(80);
            $table->tinyInteger('rest')->default(80);
            $table->tinyInteger('hygiene')->default(80);
            $table->tinyInteger('safety')->default(80);
            $table->tinyInteger('social_need')->default(80);
            $table->tinyInteger('purpose')->default(80);

            $table->string('mood')->default('content');
            $table->string('current_action')->default('idle');
            $table->text('current_action_target')->nullable();

            // location
            $table->integer('x');
            $table->integer('y');
            $table->foreignId('place_id')->nullable()->constrained('sim_places')->nullOnDelete();

            // role / economy
            $table->string('archetype')->default('dependent');
            $table->foreignId('workplace_id')->nullable()->constrained('sim_places')->nullOnDelete();
            $table->integer('last_work_tick')->default(0);

            // death tracking — first tick where any critical need hit 0
            $table->integer('starving_since_tick')->nullable();

            $table->timestamps();
        });

        Schema::create('sim_objects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('subtype');
            $table->string('material')->default('wood');
            $table->string('quality')->default('common');
            $table->string('wear')->default('used');
            $table->string('rarity')->default('mundane');

            $table->integer('weight')->default(1);
            $table->integer('size')->default(1);
            $table->integer('integrity')->default(100);
            $table->integer('value')->default(1);
            $table->integer('quantity')->default(1);

            // ownership / location
            $table->foreignId('owner_npc_id')->nullable()->constrained('sim_npcs')->nullOnDelete();
            $table->foreignId('place_id')->nullable()->constrained('sim_places')->nullOnDelete();
            $table->integer('x')->nullable();
            $table->integer('y')->nullable();

            // market
            $table->boolean('for_sale')->default(false);
            $table->integer('price')->default(0);

            // affordance — what need does interacting with this object satisfy?
            // e.g. food → hunger, bed → rest, ale → thirst+social_need
            $table->json('affordances')->nullable();

            $table->timestamps();
        });

        Schema::create('sim_actions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('verb');
            $table->foreignId('source_npc_id')->constrained('sim_npcs')->cascadeOnDelete();
            // Soft references only — objects/npcs/places may be destroyed in
            // the same tick as an action that references them (e.g. eating a
            // food deletes it). Keeping FKs caused mid-tick insert failures.
            $table->unsignedBigInteger('target_npc_id')->nullable();
            $table->unsignedBigInteger('target_object_id')->nullable();
            $table->unsignedBigInteger('place_id')->nullable();
            $table->integer('tick');
            $table->integer('duration')->default(1);
            $table->integer('difficulty')->default(0);
            $table->string('outcome')->default('pending');
            $table->string('status')->default('planned');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('sim_state', function (Blueprint $table) {
            $table->id();
            $table->integer('tick')->default(0);
            $table->string('time_of_day')->default('morning');
            $table->string('weather')->default('clear');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sim_state');
        Schema::dropIfExists('sim_actions');
        Schema::dropIfExists('sim_objects');
        Schema::dropIfExists('sim_npcs');
        Schema::dropIfExists('sim_places');
    }
};
