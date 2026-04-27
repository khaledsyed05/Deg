<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_devices')) {
            return;
        }

        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_id')->index();
            $table->string('fcm_token', 500);
            $table->string('platform', 20);
            $table->string('device_name')->nullable();
            $table->string('app_version', 30)->nullable();
            $table->string('os_version', 30)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique('device_id');
        });
    }

    public function down(): void
    {
        // Don't drop in down() — table predates this migration in production.
    }
};
