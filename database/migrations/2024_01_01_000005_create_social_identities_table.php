<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider', 50)->notNull();
            $table->string('provider_uid')->notNull();
            $table->string('provider_email')->nullable();
            $table->json('provider_meta')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_uid']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_identities');
    }
};
