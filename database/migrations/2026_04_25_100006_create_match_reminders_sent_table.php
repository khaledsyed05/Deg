<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_reminders_sent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('match_external_id', 50);
            $table->enum('reminder_type', [
                '1h_before',
                '15min_before',
                'started',
                'finished',
                'daily_summary',
                'goal_scored',
            ]);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['user_id', 'match_external_id', 'reminder_type'], 'unique_user_match_reminder');
            $table->index('match_external_id');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_reminders_sent');
    }
};
