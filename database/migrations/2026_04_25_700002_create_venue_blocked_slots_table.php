<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_blocked_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('blocked_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('reason', ['maintenance', 'private_event', 'staff_unavailable', 'other'])->default('other');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['venue_id', 'blocked_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_blocked_slots');
    }
};
