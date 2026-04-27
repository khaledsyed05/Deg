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
        Schema::table('settlements', function (Blueprint $table) {
            $table->string('receipt_path', 500)->nullable()->after('payment_reference');
            $table->text('payment_notes')->nullable()->after('receipt_path');
            $table->text('admin_notes')->nullable()->after('payment_notes');
            $table->timestamp('notes_updated_at')->nullable()->after('admin_notes');
            $table->foreignId('notes_updated_by')->nullable()->after('notes_updated_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropForeign(['notes_updated_by']);
            $table->dropColumn(['receipt_path', 'payment_notes', 'admin_notes', 'notes_updated_at', 'notes_updated_by']);
        });
    }
};
