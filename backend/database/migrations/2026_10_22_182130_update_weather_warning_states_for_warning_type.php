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
            // 地域コード単独のユニーク制約を削除
            $table->dropUnique(['area_code']);

            // 警報の種類を追加
            $table->string('warning_type')
                ->default('heavy_rain')
                ->after('area_code');

            // 地域コードと警報の種類の組み合わせを一意にする
            $table->unique(
                ['area_code', 'warning_type'],
                'weather_warning_states_area_warning_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weather_warning_states', function (Blueprint $table) {
            $table->dropUnique(
                'weather_warning_states_area_warning_unique'
            );

            $table->dropColumn('warning_type');

            $table->unique('area_code');
        });
    }
};
