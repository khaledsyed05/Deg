<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->enum('type', ['credit', 'debit'])->notNull();
            $table->unsignedInteger('amount')->notNull(); // always positive
            $table->integer('balance_after')->notNull(); // snapshot after transaction
            $table->enum('reason', ['booking_refund', 'booking_payment', 'admin_adjustment', 'cancellation_deduction'])->notNull();
            $table->string('reference_type', 100)->nullable(); // polymorphic
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
            // No updated_at — APPEND-ONLY, never UPDATE or DELETE

            $table->index('wallet_id');
            $table->index(['wallet_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
