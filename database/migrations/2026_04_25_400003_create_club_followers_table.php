<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->boolean('notify_updates')->default(true);
            $table->boolean('notify_events')->default(true);
            $table->boolean('notify_promotions')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'club_id']);
            $table->index('club_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_followers');
    }
};
