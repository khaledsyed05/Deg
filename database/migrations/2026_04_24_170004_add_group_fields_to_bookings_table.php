<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'captain_id')) {
                $table->foreignId('captain_id')->nullable()->after('user_id')
                    ->constrained('users')->nullOnDelete();
                $table->index('captain_id');
            }

            if (! Schema::hasColumn('bookings', 'team_id')) {
                $table->foreignId('team_id')->nullable()->after('captain_id')
                    ->constrained()->nullOnDelete();
                $table->index('team_id');
            }

            if (! Schema::hasColumn('bookings', 'is_group_booking')) {
                $table->boolean('is_group_booking')->default(false)->after('is_recurring');
                $table->index('is_group_booking');
            }

            if (! Schema::hasColumn('bookings', 'group_size')) {
                $table->unsignedInteger('group_size')->nullable()->after('is_group_booking');
            }

            if (! Schema::hasColumn('bookings', 'payment_split_type')) {
                $table->enum('payment_split_type', ['full', 'equal', 'custom', 'individual'])
                    ->nullable()->after('group_size');
            }

            if (! Schema::hasColumn('bookings', 'payment_split_data')) {
                $table->json('payment_split_data')->nullable()->after('payment_split_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            foreach (['captain_id', 'team_id'] as $fk) {
                if (Schema::hasColumn('bookings', $fk)) {
                    $table->dropForeign([$fk]);
                    $table->dropIndex([$fk]);
                    $table->dropColumn($fk);
                }
            }

            foreach (['is_group_booking', 'group_size', 'payment_split_type', 'payment_split_data'] as $col) {
                if (Schema::hasColumn('bookings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
