<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {

            DB::statement("
            ALTER TABLE safety_check_responses
            MODIFY COLUMN status
            ENUM('safe', 'serious', 'traffic', 'unknown', 'not_applicable')
            NOT NULL
        ");
            return;
        }

        Schema::table('safety_check_responses', function (Blueprint $table) {
            $table->enum('status', [
                'safe',
                'serious',
                'traffic',
                'unknown',
                'not_applicable',
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {

            DB::statement("
            ALTER TABLE safety_check_responses
            MODIFY COLUMN status
            ENUM('safe', 'serious', 'traffic', 'unknown')
            NOT NULL
        ");
            return;
        }

        Schema::table('safety_check_responses', function (Blueprint $table) {
            $table->enum('status', [
                'safe',
                'serious',
                'traffic',
                'unknown',
            ])->change();
        });
    }
};
