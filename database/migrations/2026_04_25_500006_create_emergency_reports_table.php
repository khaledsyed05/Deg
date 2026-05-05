<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('type', ['medical', 'safety', 'security', 'fire', 'other']);
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('description');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('location_address')->nullable();

            $table->enum('status', [
                'reported', 'acknowledged', 'in_progress', 'resolved', 'false_alarm',
            ])->default('reported');

            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->string('contact_phone', 20)->nullable();
            $table->json('attachments')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index(['type', 'severity']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_reports');
    }
};
