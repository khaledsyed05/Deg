<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Using Definition 2 — complete definition with total_bookings, total_venue_price, created_by
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('clubs')->restrictOnDelete();
            $table->date('period_from')->notNull();
            $table->date('period_to')->notNull();
            $table->unsignedInteger('total_bookings')->default(0);
            $table->unsignedInteger('total_venue_price')->default(0);
            $table->unsignedInteger('total_commission')->default(0);
            $table->unsignedInteger('total_cancellation_fees')->default(0);
            $table->unsignedInteger('net_payable')->default(0); // what platform owes club
            $table->unsignedInteger('paid_amount')->default(0); // actual amount transferred
            $table->enum('status', ['draft', 'pending', 'completed', 'cancelled'])->default('draft');
            $table->string('payment_method', 100)->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('settled_by')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('club_id');
            $table->index('status');
            $table->index(['club_id', 'period_from', 'period_to']);
            $table->index('settled_at');

            $table->foreign('settled_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
