<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->foreignId('city_id')->constrained('cities')->restrictOnDelete();
            $table->json('name')->notNull();
            $table->string('slug')->notNull()->unique();
            $table->json('description')->nullable();
            $table->string('address', 500)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->decimal('latitude', 10, 8)->notNull();
            $table->decimal('longitude', 11, 8)->notNull();
            $table->json('amenities')->nullable();
            $table->enum('status', ['pending_approval', 'active', 'inactive', 'suspended', 'rejected'])
                ->default('pending_approval');
            $table->text('rejection_reason')->nullable();
            $table->tinyInteger('is_featured')->default(0);
            $table->decimal('avg_rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('price_from')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('city_id');
            $table->index('owner_id');
            $table->index('status');
            $table->index('is_featured');
            $table->index('avg_rating');
            $table->index('price_from');
            $table->index(['city_id', 'status', 'is_featured']);
            $table->index(['latitude', 'longitude']);

            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
