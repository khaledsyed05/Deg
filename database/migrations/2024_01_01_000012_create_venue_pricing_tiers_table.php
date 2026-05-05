<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('venues')->cascadeOnDelete();
            $table->json('name')->notNull();
            $table->enum('day_type', ['all_days', 'weekday', 'weekend', 'friday', 'specific_day'])->default('all_days');
            $table->enum('specific_day', ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'])->nullable();
            $table->time('start_time')->notNull();
            $table->time('end_time')->notNull();
            $table->unsignedSmallInteger('duration_minutes')->notNull();
            $table->unsignedInteger('price')->notNull();
            $table->tinyInteger('is_active')->default(1);
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();

            $table->index('venue_id');
            $table->index('is_active');
            $table->index(['venue_id', 'is_active', 'day_type', 'start_time', 'end_time'], 'vpt_venue_day_time_idx');
            $table->index(['venue_id', 'is_active', 'duration_minutes'], 'vpt_venue_duration_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_pricing_tiers');
    }
};
