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
            $table->integer('age')->nullable()->after('name');
            $table->string('gender')->nullable()->after('age');
            $table->string('race')->nullable()->after('gender');
            $table->string('talking_mannerism')->nullable()->after('intentions');
            $table->string('talking_style')->nullable()->after('talking_mannerism');
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn(['age', 'gender', 'race', 'talking_mannerism', 'talking_style']);
        });
    }
};
