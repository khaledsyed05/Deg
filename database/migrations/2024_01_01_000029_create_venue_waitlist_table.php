<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_waitlist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('venues')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('booking_date')->notNull();
            $table->time('start_time')->notNull();
            $table->unsignedSmallInteger('duration_minutes')->notNull();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('expires_at')->notNull(); // day after booking_date
            $table->timestamp('created_at')->nullable();
            // No updated_at

            $table->unique(['venue_id', 'user_id', 'booking_date', 'start_time']);
            $table->index(['venue_id', 'booking_date', 'start_time', 'notified_at'], 'vw_venue_date_time_notify_idx');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_waitlist');
    }
};
