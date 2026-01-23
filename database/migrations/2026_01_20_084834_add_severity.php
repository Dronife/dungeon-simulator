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
            // Severity ratings (1-10) - how strongly traits affect behavior
            $table->unsignedTinyInteger('trauma_severity')->default(1);
            $table->unsignedTinyInteger('goal_severity')->default(1);
            $table->unsignedTinyInteger('intention_severity')->default(1);
            $table->unsignedTinyInteger('personality_severity')->default(1);

            // Personality axes (-1 to 1)
            $table->float('chaotic_temperature')->default(0);   // -1 orderly, 1 chaotic
            $table->float('positive_temperature')->default(0);  // -1 bitter, 1 positive
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn([
                'trauma_severity',
                'goal_severity',
                'intention_severity',
                'personality_severity',
                'chaotic_temperature',
                'positive_temperature',
            ]);
        });
    }
};
