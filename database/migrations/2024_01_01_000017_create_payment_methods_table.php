<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->json('name')->notNull();
            $table->enum('provider_key', ['syriatel_cash', 'mtn_cash', 'fatora', 'sama_pay'])->notNull()->unique();
            $table->enum('flow_type', ['otp', 'webview'])->notNull();
            $table->tinyInteger('is_active')->default(1);
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('order_column');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
