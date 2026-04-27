<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            if (! Schema::hasColumn('wallets', 'locked')) {
                $table->unsignedBigInteger('locked')->default(0)->after('balance');
            }
            if (! Schema::hasColumn('wallets', 'total_earned')) {
                $table->unsignedBigInteger('total_earned')->default(0)->after('locked');
            }
            if (! Schema::hasColumn('wallets', 'total_spent')) {
                $table->unsignedBigInteger('total_spent')->default(0)->after('total_earned');
            }
            if (! Schema::hasColumn('wallets', 'total_topup')) {
                $table->unsignedBigInteger('total_topup')->default(0)->after('total_spent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['locked', 'total_earned', 'total_spent', 'total_topup']);
        });
    }
};
