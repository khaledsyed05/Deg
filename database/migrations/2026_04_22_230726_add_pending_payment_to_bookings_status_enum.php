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

        DB::statement("ALTER TABLE bookings MODIFY status ENUM('pending_payment','confirmed','scheduled','checked_in','cancelled','completed','no_show','failed') NOT NULL DEFAULT 'pending_payment'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE bookings SET status = 'scheduled' WHERE status = 'pending_payment'");
        DB::statement("ALTER TABLE bookings MODIFY status ENUM('confirmed','scheduled','checked_in','cancelled','completed','no_show','failed') NOT NULL DEFAULT 'confirmed'");
    }
};
