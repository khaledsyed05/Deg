<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('wallet_transactions', 'idempotency_key')) {
                $table->string('idempotency_key', 128)->nullable()->after('credit_type');
                $table->unique('idempotency_key', 'wt_idempotency_key_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropUnique('wt_idempotency_key_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};
