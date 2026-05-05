<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('title_ar');
            $table->string('excerpt', 500)->nullable();
            $table->string('excerpt_ar', 500)->nullable();
            $table->longText('content');
            $table->longText('content_ar');
            $table->string('cover_image_url', 500)->nullable();
            $table->string('category', 100)->default('general');
            $table->json('tags')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_avatar_url', 500)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('reading_time_minutes')->default(5);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->timestamps();

            $table->index(['is_published', 'published_at']);
            $table->index('category');
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_articles');
    }
};
