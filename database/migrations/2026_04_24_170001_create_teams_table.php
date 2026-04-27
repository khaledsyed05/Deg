<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['casual', 'regular', 'competitive'])->default('casual');
            $table->foreignId('sport_category_id')->constrained('venue_categories')->restrictOnDelete();
            $table->foreignId('captain_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('max_members')->default(20);
            $table->boolean('is_public')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->json('regular_schedule')->nullable();
            $table->string('avatar_url')->nullable();
            $table->unsignedInteger('total_bookings')->default(0);
            $table->unsignedInteger('total_members')->default(1);
            $table->timestamps();

            $table->index('sport_category_id');
            $table->index('type');
            $table->index(['captain_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
