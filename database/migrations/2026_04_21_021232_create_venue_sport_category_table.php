<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('venue_sport_category', function (Blueprint $table) {
            $table->foreignId('venue_id')->constrained('venues')->cascadeOnDelete();
            $table->foreignId('sport_category_id')->constrained('sport_categories')->cascadeOnDelete();
            $table->primary(['venue_id', 'sport_category_id']);
            $table->index('sport_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_sport_category');
    }
};
