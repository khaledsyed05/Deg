<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_activity_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('fixture_external_id', 100);
            $table->string('platform', 16);
            $table->text('push_token');
            $table->string('activity_id', 191)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'fixture_external_id', 'platform']);
            $table->index(['fixture_external_id', 'is_active', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_activity_tokens');
    }
};
