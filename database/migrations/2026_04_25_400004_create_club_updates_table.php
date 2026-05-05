<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('title_ar');
            $table->text('content')->nullable();
            $table->text('content_ar')->nullable();
            $table->enum('type', [
                'announcement',
                'promotion',
                'event',
                'news',
                'venue_update',
                'maintenance',
            ])->default('announcement');
            $table->string('image_url', 500)->nullable();
            $table->json('attachments')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->foreignId('related_event_id')->nullable()
                ->constrained('events')->nullOnDelete();
            $table->foreignId('related_promotion_id')->nullable()
                ->constrained('promotions')->nullOnDelete();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['club_id', 'is_published']);
            $table->index(['type', 'is_published']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_updates');
    }
};
