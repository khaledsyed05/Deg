<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone_number', 20)->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('country_code', 10)->nullable()->default('+963');
            $table->string('firebase_uid', 128)->nullable()->unique();
            $table->string('firebase_provider', 50)->nullable();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('default_state_id')->nullable();
            $table->unsignedBigInteger('default_city_id')->nullable();
            $table->text('fcm_token')->nullable();
            $table->enum('fcm_platform', ['mobile', 'web'])->nullable();
            $table->enum('account_status', ['active', 'blocked', 'suspended', 'pending_profile_completion'])
                ->default('pending_profile_completion');
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->tinyInteger('notifications_push_enabled')->default(1);
            $table->tinyInteger('notifications_sms_enabled')->default(1);
            $table->tinyInteger('notifications_reminders_enabled')->default(1);
            $table->enum('preferred_language', ['ar', 'en'])->default('ar');
            $table->string('google2fa_secret')->nullable();
            $table->timestamp('google2fa_enabled_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('account_status');
            $table->index('default_city_id');
            $table->index(['account_status', 'default_city_id']);

            $table->foreign('default_state_id')->references('id')->on('states')->nullOnDelete();
            $table->foreign('default_city_id')->references('id')->on('cities')->nullOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
