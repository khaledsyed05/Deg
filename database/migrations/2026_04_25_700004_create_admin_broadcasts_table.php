<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sent_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('title_ar');
            $table->text('message');
            $table->text('message_ar');
            $table->enum('target_segment', [
                'all_users', 'active_users', 'inactive_users',
                'specific_city', 'specific_users',
            ]);
            $table->json('target_filters')->nullable();
            $table->json('target_user_ids')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->enum('status', ['pending', 'sending', 'sent', 'failed'])->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_broadcasts');
    }
};
