<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lore', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // phenomenon, creature, location, etc.
            $table->text('description');
            $table->string('occurrence')->nullable(); // rare, common, etc.
            $table->text('know_how')->nullable();
            $table->text('reason')->nullable();
            $table->text('image_prompt')->nullable();
            $table->timestamps();

            $table->index('world_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lore');
    }
};
