<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_match_states', function (Blueprint $table) {
            $table->id();
            $table->string('fixture_external_id', 100)->unique();
            $table->string('status', 50);
            $table->integer('minute')->nullable();
            $table->foreignId('home_team_id')->nullable()->constrained('football_teams')->nullOnDelete();
            $table->foreignId('away_team_id')->nullable()->constrained('football_teams')->nullOnDelete();
            $table->foreignId('league_id')->nullable()->constrained('football_leagues')->nullOnDelete();
            $table->integer('home_score')->default(0);
            $table->integer('away_score')->default(0);
            $table->string('last_event_id', 100)->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->timestamp('last_polled_at');
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('league_id');
            $table->index('last_polled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_match_states');
    }
};
