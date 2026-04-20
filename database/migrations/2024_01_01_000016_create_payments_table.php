<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('amount')->notNull();
            $table->string('currency', 3)->default('SYP');
            $table->enum('provider', ['syriatel_cash', 'mtn_cash', 'fatora', 'sama_pay', 'wallet'])->notNull();
            $table->enum('flow_type', ['otp', 'webview', 'internal'])->notNull();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled'])
                ->default('pending');
            $table->string('provider_transaction_id')->nullable();
            $table->string('provider_reference')->nullable();
            $table->json('provider_meta')->nullable(); // encrypted at model layer
            $table->json('provider_payload')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            // NOT unique on booking_id — multiple attempts allowed on failure
            $table->index('booking_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('provider');
            $table->index('provider_transaction_id');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
