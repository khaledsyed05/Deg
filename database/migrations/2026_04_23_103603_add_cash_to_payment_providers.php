<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE payments MODIFY provider ENUM('syriatel_cash','mtn_cash','fatora','sama_pay','wallet','cash') NOT NULL");
        DB::statement("ALTER TABLE payment_methods MODIFY provider_key ENUM('syriatel_cash','mtn_cash','fatora','sama_pay','cash') NOT NULL");
        DB::statement("ALTER TABLE payment_methods MODIFY flow_type ENUM('otp','webview','internal') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE payments SET provider = 'wallet' WHERE provider = 'cash'");
        DB::statement("DELETE FROM payment_methods WHERE provider_key = 'cash'");
        DB::statement("ALTER TABLE payments MODIFY provider ENUM('syriatel_cash','mtn_cash','fatora','sama_pay','wallet') NOT NULL");
        DB::statement("ALTER TABLE payment_methods MODIFY provider_key ENUM('syriatel_cash','mtn_cash','fatora','sama_pay') NOT NULL");
    }
};
