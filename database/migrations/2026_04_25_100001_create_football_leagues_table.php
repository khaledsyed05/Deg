<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('football_leagues', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 50)->unique()->comment('ID from football-data.org');
            $table->string('code', 20)->unique()->comment('PL, PD, CL, etc.');
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('country');
            $table->string('country_ar')->nullable();
            $table->string('emblem_url', 500)->nullable();
            $table->enum('type', ['LEAGUE', 'CUP', 'CHAMPIONSHIP'])->default('LEAGUE');
            $table->date('current_season_start')->nullable();
            $table->date('current_season_end')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('display_order')->default(0);
            $table->integer('api_sports_id')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('football_leagues');
    }
};
