<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_match_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('match_reminders_enabled')->default(true);
            $table->integer('reminder_minutes_before')->default(60);
            $table->boolean('second_reminder_enabled')->default(false);
            $table->integer('second_reminder_minutes_before')->default(15);
            $table->boolean('goal_notifications_enabled')->default(false);
            $table->boolean('result_notifications_enabled')->default(true);
            $table->boolean('daily_summary_enabled')->default(true);
            $table->time('daily_summary_time')->default('09:00:00');
            $table->boolean('quiet_hours_enabled')->default(false);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_match_notification_settings');
    }
};
