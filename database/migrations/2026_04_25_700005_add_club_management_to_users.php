<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'managed_club_id')) {
                $table->foreignId('managed_club_id')->nullable()
                    ->constrained('clubs')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'staff_club_id')) {
                $table->foreignId('staff_club_id')->nullable()
                    ->constrained('clubs')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['managed_club_id', 'staff_club_id'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropForeign([$col]);
                    $table->dropColumn($col);
                }
            }
        });
    }
};
