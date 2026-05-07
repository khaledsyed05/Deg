<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conversations')) {
            return;
        }

        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->enum('type', ['dm', 'team', 'group']);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'reference_id']);
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
