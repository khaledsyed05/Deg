<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('club_id')->constrained('clubs')->restrictOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->restrictOnDelete();
            $table->decimal('rating', 2, 1)->notNull(); // 1.0 – 5.0
            $table->text('body')->nullable(); // no minimum length (LOCK/CC-007 patch)
            $table->tinyInteger('is_anonymous')->default(0);
            $table->string('venue_hint')->nullable(); // denormalized snapshot
            $table->tinyInteger('is_published')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->unsignedBigInteger('hidden_by')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'club_id']); // one review per player per club
            $table->index('club_id');
            $table->index('is_published');
            $table->index(['club_id', 'is_published', 'created_at']);
            $table->index('booking_id');
            $table->index('rating');

            $table->foreign('hidden_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
