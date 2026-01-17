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
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_player')->default(false);

            // Character attributes
            $table->text('info')->nullable();
            $table->text('personality')->nullable();
            $table->text('traits')->nullable();
            $table->text('trauma')->nullable();
            $table->text('hobbies')->nullable();
            $table->text('routines')->nullable();
            $table->text('job')->nullable();
            $table->text('skills')->nullable();
            $table->text('goals')->nullable();
            $table->text('secrets')->nullable();
            $table->text('limits')->nullable();
            $table->text('intentions')->nullable();

            // LLM config
            $table->float('temperature')->default(0.7);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
