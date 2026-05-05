<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'qr_code')) {
                $table->text('qr_code')->nullable()->after('booking_code');
            }

            if (! Schema::hasColumn('bookings', 'checked_in_at')) {
                $table->timestamp('checked_in_at')->nullable()->after('cancelled_at');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bookings MODIFY status ENUM('confirmed','scheduled','cancelled','completed','no_show','failed','checked_in') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE bookings SET status = 'confirmed' WHERE status = 'checked_in'");
            DB::statement("ALTER TABLE bookings MODIFY status ENUM('confirmed','scheduled','cancelled','completed','no_show','failed') NOT NULL");
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'qr_code')) {
                $table->dropColumn('qr_code');
            }
            if (Schema::hasColumn('bookings', 'checked_in_at')) {
                $table->dropColumn('checked_in_at');
            }
        });
    }
};
