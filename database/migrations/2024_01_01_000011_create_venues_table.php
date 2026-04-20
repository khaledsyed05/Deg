<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->json('name')->notNull();
            $table->json('description')->nullable();
            $table->string('size', 50)->nullable();
            $table->json('amenities')->nullable();
            $table->json('opening_hours')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('avg_rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('price_from')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->unsignedInteger('order_column')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index('club_id');
            $table->index('category_id');
            $table->index('status');
            $table->index(['club_id', 'status', 'order_column']);

            $table->foreign('category_id')->references('id')->on('venue_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
