<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'subscription_id')) {
                $table->foreignId('subscription_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained()
                    ->nullOnDelete();
                $table->index('subscription_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'subscription_id')) {
                $table->dropForeign(['subscription_id']);
                $table->dropIndex(['subscription_id']);
                $table->dropColumn('subscription_id');
            }
        });
    }
};
