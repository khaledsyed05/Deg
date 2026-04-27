<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ticket_messages')) {
            Schema::create('ticket_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
                $table->enum('sender_type', ['user', 'agent', 'system']);
                $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('message');
                $table->json('attachments')->nullable();
                $table->boolean('is_internal_note')->default(false);
                $table->timestamps();

                $table->index('ticket_id');
                $table->index(['ticket_id', 'created_at']);
            });
        }

        Schema::table('support_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('support_tickets', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable();
            }
            if (! Schema::hasColumn('support_tickets', 'assigned_agent_id')) {
                $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('support_tickets', 'status_history')) {
                $table->json('status_history')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            foreach (['status_history', 'last_activity_at'] as $col) {
                if (Schema::hasColumn('support_tickets', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('support_tickets', 'assigned_agent_id')) {
                $table->dropConstrainedForeignId('assigned_agent_id');
            }
        });

        Schema::dropIfExists('ticket_messages');
    }
};
