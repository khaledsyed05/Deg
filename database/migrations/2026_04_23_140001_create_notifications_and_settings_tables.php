<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
            });
        }

        if (! Schema::hasTable('notification_settings')) {
            Schema::create('notification_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('notification_type');
                $table->boolean('enabled')->default(true);
                $table->boolean('push_enabled')->default(true);
                $table->boolean('sms_enabled')->default(false);
                $table->boolean('email_enabled')->default(false);
                $table->timestamps();

                $table->unique(['user_id', 'notification_type']);
                $table->index('notification_type');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone', 64)->default('Asia/Damascus')->after('preferred_language');
            }
            if (! Schema::hasColumn('users', 'privacy_settings')) {
                $table->json('privacy_settings')->nullable();
            }
            if (! Schema::hasColumn('users', 'preferences')) {
                $table->json('preferences')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['timezone', 'privacy_settings', 'preferences'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('notification_settings');
        Schema::dropIfExists('notifications');
    }
};
