<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->string('title_ar');
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('cover_image_url', 500)->nullable();
            $table->json('gallery_urls')->nullable();

            $table->enum('type', [
                'tournament',
                'training',
                'social',
                'exhibition',
                'workshop',
                'camp',
            ]);
            $table->string('sport_type', 50)->nullable();

            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('registration_opens_at')->nullable();
            $table->timestamp('registration_closes_at');

            $table->unsignedInteger('max_participants');
            $table->unsignedInteger('min_participants')->default(1);
            $table->unsignedInteger('current_participants')->default(0);
            $table->decimal('registration_fee', 12, 2)->default(0);
            $table->enum('participant_type', ['individual', 'team'])->default('individual');
            $table->unsignedInteger('team_size')->nullable();

            $table->json('prize_structure')->nullable();
            $table->text('rules')->nullable();
            $table->text('rules_ar')->nullable();
            $table->text('requirements')->nullable();
            $table->text('requirements_ar')->nullable();

            $table->enum('status', [
                'draft',
                'open',
                'closed',
                'in_progress',
                'completed',
                'cancelled',
            ])->default('draft');

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('views_count')->default(0);

            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index('club_id');
            $table->index('is_featured');
            $table->index(['type', 'sport_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
