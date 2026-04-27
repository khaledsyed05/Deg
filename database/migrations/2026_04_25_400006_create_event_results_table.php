<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_id')->nullable()
                ->constrained('event_registrations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedInteger('rank');
            $table->json('stats')->nullable();
            $table->decimal('prize_amount', 12, 2)->nullable();
            $table->string('prize_description')->nullable();
            $table->boolean('prize_paid')->default(false);
            $table->timestamp('prize_paid_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['event_id', 'rank']);
            $table->unique(['event_id', 'registration_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_results');
    }
};
