<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Events tab only — NOT used for venue categorisation
        Schema::create('sport_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name')->notNull();
            $table->string('slug', 100)->notNull()->unique();
            $table->unsignedInteger('order_column')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();

            $table->index('is_active');
            $table->index('order_column');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_categories');
    }
};
