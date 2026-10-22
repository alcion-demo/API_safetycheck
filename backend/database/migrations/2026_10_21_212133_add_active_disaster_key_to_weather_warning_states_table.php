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
            $table->string('active_disaster_key')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weather_warning_states', function (Blueprint $table) {
            $table->dropColumn('active_disaster_key');
        });
    }
};
