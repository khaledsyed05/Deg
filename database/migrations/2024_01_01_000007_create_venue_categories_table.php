<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name')->notNull();
            $table->string('slug', 100)->notNull()->unique();
            $table->enum('type', ['sports', 'hall', 'court', 'outdoor', 'other'])->default('sports');
            $table->tinyInteger('is_active')->default(1);
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('type');
            $table->index('order_column');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_categories');
    }
};
