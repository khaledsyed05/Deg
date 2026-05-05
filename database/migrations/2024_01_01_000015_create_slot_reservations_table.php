<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slot_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('venues')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('booking_date')->notNull();
            $table->time('start_time')->notNull();
            $table->time('end_time')->notNull();
            $table->unsignedSmallInteger('duration_minutes')->notNull();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->timestamp('reserved_until')->notNull();
            $table->timestamp('created_at')->nullable();
            // No updated_at — created once, deleted on success/expiry

            $table->unique(['venue_id', 'booking_date', 'start_time']);
            $table->index(['venue_id', 'booking_date', 'reserved_until']);
            $table->index('reserved_until');
            $table->index('user_id');

            $table->foreign('category_id')->references('id')->on('venue_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_reservations');
    }
};
