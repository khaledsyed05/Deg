<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', ['ios', 'android'])->index();
            $table->string('version', 20);
            $table->integer('build_number');
            $table->boolean('is_force_update')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('release_notes')->nullable();
            $table->text('release_notes_ar')->nullable();
            $table->date('released_at');
            $table->timestamps();

            $table->unique(['platform', 'version']);
            $table->index(['platform', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
