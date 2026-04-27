<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_bookings')->default(0);
            $table->unsignedInteger('completed_bookings')->default(0);
            $table->unsignedInteger('cancelled_bookings')->default(0);
            $table->unsignedInteger('total_hours_played')->default(0);
            $table->unsignedBigInteger('total_spent')->default(0);
            $table->foreignId('favorite_sport_id')->nullable()->constrained('venue_categories')->nullOnDelete();
            $table->foreignId('favorite_venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->decimal('average_rating_given', 3, 2)->nullable();
            $table->unsignedInteger('bookings_this_month')->default(0);
            $table->unsignedInteger('streak_days')->default(0);
            $table->date('last_booking_date')->nullable();
            $table->timestamps();

            $table->index('total_bookings');
            $table->index('total_spent');
            $table->index('total_hours_played');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_stats');
    }
};
