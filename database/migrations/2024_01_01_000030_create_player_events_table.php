<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only behavioral tracking table — Super Admin access only (LOCK-008)
        Schema::create('player_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // NULL for unauthenticated users
            $table->string('anonymous_id', 64)->nullable(); // device-generated UUID
            $table->string('session_id', 64)->notNull();
            $table->string('event_name', 100)->notNull();
            $table->json('properties')->nullable();
            $table->timestamp('occurred_at')->notNull(); // device time
            $table->timestamp('created_at')->nullable(); // server receipt time
            // No updated_at — APPEND-ONLY

            $table->index(['user_id', 'event_name', 'occurred_at']);
            $table->index(['event_name', 'occurred_at']);
            $table->index('session_id');
            $table->index('anonymous_id');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_events');
    }
};
