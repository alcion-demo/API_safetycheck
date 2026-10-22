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
        Schema::table('weather_warning_states', function (Blueprint $table) {
            $table->string('area_code')->unique()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weather_warning_states', function (Blueprint $table) {
            $table->dropUnique(['area_code']);
            $table->dropColumn('area_code');
        });
    }
};
