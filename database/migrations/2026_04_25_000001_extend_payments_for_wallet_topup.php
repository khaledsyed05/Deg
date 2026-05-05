<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make booking_id nullable + drop strict FK so wallet_topup payments don't need a booking.
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->change();
            $table->foreign('booking_id')->references('id')->on('bookings')->restrictOnDelete();

            if (! Schema::hasColumn('payments', 'payment_type')) {
                $table->enum('payment_type', ['booking', 'wallet_topup'])
                    ->default('booking')
                    ->after('amount');
            }
            if (! Schema::hasColumn('payments', 'wallet_id')) {
                $table->foreignId('wallet_id')->nullable()->after('user_id')
                    ->constrained('wallets')->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'bonus_amount')) {
                $table->unsignedBigInteger('bonus_amount')->default(0)->after('amount');
            }
            if (! Schema::hasColumn('payments', 'total_credited')) {
                $table->unsignedBigInteger('total_credited')->nullable()->after('bonus_amount');
            }

            $table->index('payment_type', 'payments_payment_type_idx');
            $table->index(['user_id', 'payment_type', 'status'], 'payments_user_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_payment_type_idx');
            $table->dropIndex('payments_user_type_status_idx');
            $table->dropForeign(['wallet_id']);
            $table->dropColumn(['payment_type', 'wallet_id', 'bonus_amount', 'total_credited']);
        });
    }
};
