<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_relationships', function (Blueprint $table) {
            $table->id();
            $table->float('trust')->default(0);
            $table->float('affection')->default(0);
            $table->float('respect')->default(0);
            $table->float('fear')->default(0);
            $table->float('loyalty')->default(0);
            $table->float('debt')->default(0);
            $table->float('rivalry')->default(0);
            $table->float('attraction')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_relationships');
    }
};