<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('address', 500)->nullable()->after('default_city_id');
            $table->text('block_reason')->nullable()->after('account_status');
            $table->timestamp('blocked_at')->nullable()->after('block_reason');
            $table->foreignId('blocked_by')->nullable()->after('blocked_at')->constrained('users')->nullOnDelete();
            $table->timestamp('unblocked_at')->nullable()->after('blocked_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['blocked_by']);
            $table->dropColumn(['address', 'block_reason', 'blocked_at', 'blocked_by', 'unblocked_at']);
        });
    }
};
