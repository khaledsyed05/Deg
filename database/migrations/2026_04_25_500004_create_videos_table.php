<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->text('description_ar')->nullable();
            $table->string('youtube_id', 50)->nullable();
            $table->string('youtube_url', 500)->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('category', 100)->default('tutorial');
            $table->json('tags')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
