<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->json('title')->notNull();
            $table->json('description')->nullable();
            $table->date('start_date')->notNull();
            $table->date('end_date')->nullable();
            $table->tinyInteger('is_published')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('is_published');
            $table->index(['is_published', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
