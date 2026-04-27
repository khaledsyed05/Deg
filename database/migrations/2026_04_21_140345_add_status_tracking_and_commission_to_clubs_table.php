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
        Schema::table('clubs', function (Blueprint $table) {
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            $table->foreignId('rejected_by')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            $table->timestamp('suspended_at')->nullable()->after('rejected_by');
            $table->foreignId('suspended_by')->nullable()->after('suspended_at')->constrained('users')->nullOnDelete();
            $table->text('suspension_reason')->nullable()->after('suspended_by');
            $table->timestamp('unsuspended_at')->nullable()->after('suspension_reason');
            $table->decimal('commission_rate', 5, 2)->nullable()->after('unsuspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['suspended_by']);
            $table->dropColumn([
                'rejected_at',
                'rejected_by',
                'suspended_at',
                'suspended_by',
                'suspension_reason',
                'unsuspended_at',
                'commission_rate',
            ]);
        });
    }
};
