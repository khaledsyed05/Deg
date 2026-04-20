<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained('venues')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            // No updated_at — pivot table

            $table->unique(['user_id', 'venue_id']);
            $table->index('venue_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_venues');
    }
};
