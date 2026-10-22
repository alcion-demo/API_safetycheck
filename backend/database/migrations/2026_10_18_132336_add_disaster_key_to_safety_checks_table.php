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
        Schema::table('safety_checks', function (Blueprint $table) {
            $table->string('disaster_key')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('safety_checks', function (Blueprint $table) {
            $table->dropUnique(['disaster_key']);
            $table->dropColumn('disaster_key');
        });
    }
};
