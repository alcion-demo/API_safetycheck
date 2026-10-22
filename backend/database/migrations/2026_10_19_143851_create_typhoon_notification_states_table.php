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
        Schema::create('typhoon_notification_states', function (Blueprint $table) {
            $table->id();

            // 台風と予報の識別情報
            $table->string('event_id');
            $table->string('region_name');
            $table->string('forecast_period');

            // 最新の確率と通知状態
            $table->unsignedTinyInteger('latest_probability')->nullable();
            $table->unsignedTinyInteger('last_notified_probability')->nullable();
            $table->string('last_notified_serial')->nullable();
            $table->boolean('below_threshold')->default(true);

            $table->timestamps();

            // 台風・地域・積算期間ごとに1レコード
            $table->unique(
                ['event_id', 'region_name', 'forecast_period'],
                'typhoon_notification_states_target_unique'
            );
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('typhoon_notification_states');
    }
};
