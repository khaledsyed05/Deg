<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('wallet_transactions', 'credit_type')) {
                $table->string('credit_type', 32)->nullable()->after('type')
                    ->comment('topup, promotional, referral, refund, transfer_in, transfer_out, booking, withdrawal, bonus');
            }
            if (! Schema::hasColumn('wallet_transactions', 'description')) {
                $table->string('description')->nullable()->after('note');
            }
            if (! Schema::hasColumn('wallet_transactions', 'metadata')) {
                $table->json('metadata')->nullable()->after('description');
            }
            if (! Schema::hasColumn('wallet_transactions', 'status')) {
                $table->string('status', 16)->default('completed')->after('metadata');
            }
            if (! Schema::hasColumn('wallet_transactions', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('wallet_transactions', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('expires_at');
            }

            $table->index('credit_type', 'wt_credit_type_idx');
            $table->index('status', 'wt_status_idx');
            $table->index('expires_at', 'wt_expires_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropIndex('wt_credit_type_idx');
            $table->dropIndex('wt_status_idx');
            $table->dropIndex('wt_expires_at_idx');
            $table->dropColumn([
                'credit_type',
                'description',
                'metadata',
                'status',
                'expires_at',
                'processed_at',
            ]);
        });
    }
};
