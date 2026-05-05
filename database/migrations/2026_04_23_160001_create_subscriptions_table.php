<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->restrictOnDelete();
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly', 'custom']);
            $table->unsignedInteger('interval')->nullable();
            $table->string('day_of_week')->nullable();
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->time('start_time');
            $table->unsignedInteger('duration_hours');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['pending', 'active', 'paused', 'cancelled', 'expired'])->default('pending');
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('auto_pay')->default(true);
            $table->unsignedInteger('price_per_booking');
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->date('next_booking_date')->nullable();
            $table->date('next_charge_date')->nullable();
            $table->unsignedInteger('total_bookings_created')->default(0);
            $table->unsignedInteger('pause_count')->default(0);
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('venue_id');
            $table->index('status');
            $table->index('next_booking_date');
            $table->index(['status', 'next_booking_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
