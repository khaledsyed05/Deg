<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_challenges', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->notNull()->unique();
            $table->string('phone_number', 20)->notNull();
            $table->string('code_hash')->notNull();
            $table->enum('channel', ['sms', 'whatsapp'])->default('sms');
            $table->timestamp('expires_at')->notNull();
            $table->timestamp('consumed_at')->nullable();
            $table->unsignedTinyInteger('attempts_count')->default(0);
            $table->unsignedTinyInteger('resend_count')->default(0);
            $table->enum('delivery_status', ['pending', 'sent', 'failed'])->default('pending');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();
            // No updated_at — append-only

            $table->index('phone_number');
            $table->index('expires_at');
            $table->index(['phone_number', 'consumed_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_challenges');
    }
};
