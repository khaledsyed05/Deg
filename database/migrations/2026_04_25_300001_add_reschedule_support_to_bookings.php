<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'rescheduled_from_booking_id')) {
                $table->foreignId('rescheduled_from_booking_id')->nullable()->after('id')
                    ->constrained('bookings')->nullOnDelete();
                $table->index('rescheduled_from_booking_id');
            }
            if (! Schema::hasColumn('bookings', 'reschedule_history')) {
                $table->json('reschedule_history')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'reschedule_count')) {
                $table->integer('reschedule_count')->default(0);
            }
            if (! Schema::hasColumn('bookings', 'refund_status')) {
                $table->enum('refund_status', ['none', 'requested', 'partially_refunded', 'fully_refunded'])
                    ->default('none')->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            foreach (['refund_status', 'reschedule_count', 'reschedule_history'] as $col) {
                if (Schema::hasColumn('bookings', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('bookings', 'rescheduled_from_booking_id')) {
                $table->dropConstrainedForeignId('rescheduled_from_booking_id');
            }
        });
    }
};
