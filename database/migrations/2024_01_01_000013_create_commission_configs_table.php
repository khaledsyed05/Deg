<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_configs', function (Blueprint $table) {
            $table->id();
            $table->enum('scope', ['global', 'club', 'venue'])->default('global');
            $table->unsignedBigInteger('club_id')->nullable();
            $table->unsignedBigInteger('venue_id')->nullable();
            $table->enum('commission_type', ['fixed', 'percentage'])->default('fixed');
            $table->unsignedInteger('commission_value')->default(0);
            // fixed = SYP amount | percentage = basis points (e.g. 700 = 7.00%)
            // apply_as REMOVED — commission is ALWAYS deducted from club (LOCK-001)
            $table->unsignedInteger('cancellation_fee')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->date('effective_from')->default(\DB::raw('(CURRENT_DATE)'));
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('scope');
            $table->index('is_active');
            $table->index('effective_from');
            $table->index('club_id');
            $table->index('venue_id');
            $table->index(['scope', 'venue_id', 'is_active']);

            $table->foreign('club_id')->references('id')->on('clubs')->cascadeOnDelete();
            $table->foreign('venue_id')->references('id')->on('venues')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_configs');
    }
};
