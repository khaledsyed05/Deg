<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Using Definition 2 — column names aligned with bookings table
        Schema::create('settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('settlements')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->restrictOnDelete();
            $table->unsignedInteger('venue_price')->notNull();
            $table->unsignedInteger('commission_amount')->notNull();
            $table->unsignedInteger('club_payout_amount')->notNull();
            $table->unsignedInteger('cancellation_comm')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->unique(['settlement_id', 'booking_id']);
            $table->index('settlement_id');
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_items');
    }
};
