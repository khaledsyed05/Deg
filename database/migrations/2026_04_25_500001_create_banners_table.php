<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_ar');
            $table->text('subtitle')->nullable();
            $table->text('subtitle_ar')->nullable();
            $table->string('image_url', 500);
            $table->string('image_url_dark', 500)->nullable();
            $table->string('link_type', 50)->nullable();
            $table->string('link_value', 500)->nullable();
            $table->enum('position', [
                'home_top', 'home_middle', 'home_bottom',
                'venue_list', 'event_list', 'profile',
            ])->default('home_top');
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('clicks_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->index(['position', 'is_active']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
