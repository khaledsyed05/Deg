<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_flash_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('venues')->cascadeOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('start_datetime')->notNull();
            $table->dateTime('end_datetime')->notNull();
            $table->unsignedInteger('original_price')->notNull();
            $table->unsignedInteger('discounted_price')->notNull();
            $table->unsignedTinyInteger('max_bookings')->nullable(); // NULL = unlimited
            $table->unsignedTinyInteger('bookings_count')->default(0);
            $table->dateTime('expires_at')->notNull();
            $table->enum('status', ['active', 'expired', 'fully_booked', 'cancelled'])->default('active');
            $table->timestamps();

            $table->index(['venue_id', 'status', 'expires_at']);
            $table->index(['status', 'start_datetime']);

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        // MySQL 8.0+ check constraints (not supported by SQLite)
        if (\DB::getDriverName() === 'mysql') {
            \DB::statement('ALTER TABLE venue_flash_deals ADD CONSTRAINT chk_flash_deal_price CHECK (discounted_price < original_price)');
            \DB::statement('ALTER TABLE venue_flash_deals ADD CONSTRAINT chk_flash_deal_dates CHECK (end_datetime > start_datetime)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_flash_deals');
    }
};
