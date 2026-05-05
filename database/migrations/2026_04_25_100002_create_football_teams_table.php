<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('football_teams', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 50)->unique();
            $table->string('name');
            $table->string('short_name', 100)->nullable();
            $table->string('name_ar')->nullable();
            $table->string('tla', 10)->nullable();
            $table->string('crest_url', 500)->nullable();
            $table->foreignId('league_id')->nullable()->constrained('football_leagues')->nullOnDelete();
            $table->string('country')->nullable();
            $table->string('venue_name')->nullable();
            $table->integer('founded_year')->nullable();
            $table->boolean('is_popular')->default(false);
            $table->integer('popularity_rank')->nullable();
            $table->integer('api_sports_id')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('is_popular');
            $table->index('league_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('football_teams');
    }
};
