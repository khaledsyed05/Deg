<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_favorite_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('football_teams')->cascadeOnDelete();
            $table->integer('display_order')->default(0);
            $table->boolean('notify_matches')->default(true);
            $table->boolean('notify_goals')->default(false);
            $table->boolean('notify_results')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'team_id']);
            $table->index(['user_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_favorite_teams');
    }
};
