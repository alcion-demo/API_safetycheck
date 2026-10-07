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
        Schema::create('safety_checks', function (Blueprint $table) {
            $table->id();

            // 本番 / 訓練
             $table->string('type');

            $table->string('title');
            $table->text('message');

            // 予約日時（主に訓練）
            $table->dateTime('scheduled_at')->nullable();

            // scheduled / active / closed
            $table->string('status');

            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('safety_checks');
    }
};
