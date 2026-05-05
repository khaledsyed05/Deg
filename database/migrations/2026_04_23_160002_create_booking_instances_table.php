<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->date('scheduled_date');
            $table->time('scheduled_start_time');
            $table->time('scheduled_end_time');
            $table->enum('status', ['scheduled', 'created', 'skipped', 'failed'])->default('scheduled');
            $table->enum('payment_status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('skipped_reason')->nullable();
            $table->text('failure_reason')->nullable();
            $table->unsignedTinyInteger('payment_attempts')->default(0);
            $table->timestamp('last_payment_attempt_at')->nullable();
            $table->timestamps();

            $table->index('scheduled_date');
            $table->index(['subscription_id', 'scheduled_date']);
            $table->index(['status', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_instances');
    }
};
