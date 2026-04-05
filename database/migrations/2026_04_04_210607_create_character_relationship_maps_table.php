<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_relationship_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('to_character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('character_relationship_id')->constrained('character_relationships')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_relationship_maps');
    }
};