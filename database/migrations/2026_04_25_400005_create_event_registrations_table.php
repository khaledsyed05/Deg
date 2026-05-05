<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();

            $table->string('registration_number', 50)->unique();
            $table->enum('status', [
                'pending_payment',
                'confirmed',
                'cancelled',
                'attended',
                'no_show',
                'refunded',
            ])->default('pending_payment');

            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->timestamp('paid_at')->nullable();

            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('refund_request_id')->nullable()
                ->constrained('refund_requests')->nullOnDelete();

            $table->json('participant_info')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();

            $table->unique(['event_id', 'user_id'], 'unique_event_user_registration');
            $table->index(['event_id', 'status']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
