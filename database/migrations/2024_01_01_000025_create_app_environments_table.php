<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_environments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained('app_platforms')->cascadeOnDelete();
            $table->string('name', 50)->notNull();
            $table->string('base_url', 500)->notNull();
            $table->tinyInteger('is_active')->default(0);
            $table->timestamps();

            $table->index('platform_id');
            $table->index(['platform_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_environments');
    }
};
