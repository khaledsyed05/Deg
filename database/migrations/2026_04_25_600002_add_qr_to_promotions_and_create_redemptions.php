<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            if (! Schema::hasColumn('promotions', 'qr_code')) {
                $table->string('qr_code', 100)->nullable()->unique()->after('slug');
            }
            if (! Schema::hasColumn('promotions', 'is_qr_promotion')) {
                $table->boolean('is_qr_promotion')->default(false)->after('qr_code');
            }
            if (! Schema::hasColumn('promotions', 'qr_redemption_limit')) {
                $table->unsignedInteger('qr_redemption_limit')->default(1)->after('is_qr_promotion');
            }
            if (! Schema::hasColumn('promotions', 'qr_redemption_count')) {
                $table->unsignedInteger('qr_redemption_count')->default(0)->after('qr_redemption_limit');
            }
        });

        Schema::create('qr_promotion_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('redeemed_at');
            $table->timestamps();

            $table->unique(['promotion_id', 'user_id'], 'unique_user_qr_redemption');
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'applied_promotion_id')) {
                $table->foreignId('applied_promotion_id')->nullable()
                    ->constrained('promotions')->nullOnDelete();
            }
            if (! Schema::hasColumn('bookings', 'discount_amount')) {
                $table->unsignedInteger('discount_amount')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_promotion_redemptions');

        Schema::table('promotions', function (Blueprint $table) {
            foreach (['qr_code', 'is_qr_promotion', 'qr_redemption_limit', 'qr_redemption_count'] as $col) {
                if (Schema::hasColumn('promotions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'applied_promotion_id')) {
                $table->dropForeign(['applied_promotion_id']);
                $table->dropColumn('applied_promotion_id');
            }
            if (Schema::hasColumn('bookings', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
        });
    }
};
