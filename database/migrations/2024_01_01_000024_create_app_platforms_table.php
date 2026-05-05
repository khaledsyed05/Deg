<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('platform_key', 50)->notNull()->unique();
            $table->json('name')->notNull();
            $table->string('latest_version', 20)->notNull();
            $table->string('minimum_required_version', 20)->notNull();
            $table->string('store_url', 500)->nullable();
            $table->string('direct_apk_url', 500)->nullable();
            $table->tinyInteger('direct_apk_enabled')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->unsignedInteger('order_column')->default(0);
            $table->unsignedBigInteger('last_updated_by')->nullable();
            $table->timestamps();

            $table->index('is_active');

            $table->foreign('last_updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_platforms');
    }
};
