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
        Schema::create('world_hooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // threat, rumor, faction, local_color
            $table->text('brief');
            $table->text('situation');
            $table->text('stakes')->nullable();
            $table->text('clue')->nullable();
            $table->text('image_prompt')->nullable();
            $table->text('image_path')->nullable();
            $table->unsignedTinyInteger('image_cell_index')->nullable();
            $table->timestamps();

            $table->index('world_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('world_hooks');
    }
};
