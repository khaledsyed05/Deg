<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_payment_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'paid', 'overdue', 'waived'])->default('pending');
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('payment_due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'user_id']);
            $table->index(['booking_id', 'status']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'is_split_payment')) {
                $table->boolean('is_split_payment')->default(false);
            }
            if (! Schema::hasColumn('bookings', 'split_method')) {
                $table->enum('split_method', ['equal', 'custom'])->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_payment_splits');

        Schema::table('bookings', function (Blueprint $table) {
            foreach (['is_split_payment', 'split_method'] as $col) {
                if (Schema::hasColumn('bookings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
