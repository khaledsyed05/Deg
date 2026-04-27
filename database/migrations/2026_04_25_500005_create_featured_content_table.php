<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('featured_content', function (Blueprint $table) {
            $table->id();
            $table->morphs('contentable');
            $table->string('section', 50);
            $table->string('title_ar')->nullable();
            $table->string('subtitle_ar')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['section', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_content');
    }
};
