<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreignId('venue_id')->constrained('venues')->restrictOnDelete();
            $table->unsignedBigInteger('sport_category_id')->nullable();
            $table->string('booking_code', 20)->notNull()->unique();
            $table->enum('source', ['mobile', 'manual'])->default('mobile');
            $table->enum('manual_type', ['external', 'blocked'])->nullable();
            $table->text('manual_note')->nullable();
            $table->enum('status', ['confirmed', 'scheduled', 'cancelled', 'completed', 'no_show', 'failed'])->notNull();
            // No 'pending' status — booking row created only after payment succeeds
            $table->date('booking_date')->notNull();
            $table->time('start_time')->notNull();
            $table->time('end_time')->notNull();
            $table->dateTime('starts_at')->notNull();
            $table->dateTime('ends_at')->notNull();
            $table->unsignedSmallInteger('duration_minutes')->notNull();
            $table->unsignedInteger('venue_price')->default(0);
            $table->unsignedInteger('commission_amount')->default(0);
            $table->enum('commission_type', ['fixed', 'percentage'])->nullable();
            $table->unsignedInteger('total_price')->default(0);
            $table->unsignedInteger('club_payout_amount')->default(0);
            $table->unsignedInteger('cancellation_commission')->default(0);
            $table->string('currency', 3)->default('SYP');
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->tinyInteger('is_recurring')->default(0);
            $table->json('recurrence_pattern')->nullable();
            $table->unsignedBigInteger('recurrence_parent_id')->nullable();
            $table->timestamp('reminder_2h_sent_at')->nullable();
            $table->timestamp('reminder_1h_sent_at')->nullable();
            // Deposit fields — LOCK-002
            $table->unsignedInteger('deposit_amount')->default(0);
            $table->enum('deposit_status', ['none', 'paid'])->default('none');
            $table->unsignedInteger('remaining_amount')->default(0);
            $table->enum('remaining_status', ['none', 'due_on_arrival', 'confirmed', 'waived'])->default('none');
            $table->timestamp('remaining_confirmed_at')->nullable();
            $table->unsignedBigInteger('remaining_confirmed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('venue_id');
            $table->index('status');
            $table->index('booking_date');
            $table->index('starts_at');
            $table->index(['venue_id', 'booking_date', 'status']);
            $table->index(['venue_id', 'booking_date', 'start_time', 'end_time', 'status']);
            $table->index(['status', 'booking_date', 'reminder_2h_sent_at']);
            $table->index('recurrence_parent_id');
            $table->index('source');
            $table->index('remaining_status');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('sport_category_id')->references('id')->on('sport_categories')->nullOnDelete();
            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('recurrence_parent_id')->references('id')->on('bookings')->nullOnDelete();
            $table->foreign('remaining_confirmed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
