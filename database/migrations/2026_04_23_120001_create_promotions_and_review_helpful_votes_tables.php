<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('club_id')->nullable()->constrained('clubs')->nullOnDelete();
                $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();
                $table->string('code', 50)->unique();
                $table->string('slug', 100)->unique();
                $table->json('name');
                $table->json('description')->nullable();
                $table->enum('type', ['percentage', 'fixed_amount', 'free_hours']);
                $table->unsignedInteger('value');
                $table->unsignedInteger('min_amount')->nullable();
                $table->unsignedInteger('max_discount')->nullable();
                $table->timestamp('valid_from')->nullable();
                $table->timestamp('valid_to')->nullable();
                $table->unsignedInteger('max_uses')->nullable();
                $table->unsignedInteger('max_uses_per_user')->nullable();
                $table->unsignedInteger('current_uses')->default(0);
                $table->enum('applies_to', ['all', 'venues', 'categories'])->default('all');
                $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
                $table->boolean('is_featured')->default(false);
                $table->boolean('first_booking_only')->default(false);
                $table->json('allowed_days')->nullable();
                $table->string('image_url')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('status');
                $table->index('is_featured');
                $table->index(['valid_from', 'valid_to']);
                $table->index(['club_id', 'status']);
            });
        } else {
            Schema::table('promotions', function (Blueprint $table) {
                if (! Schema::hasColumn('promotions', 'venue_id')) {
                    $table->foreignId('venue_id')->nullable()->after('club_id')->constrained('venues')->nullOnDelete();
                }
                if (! Schema::hasColumn('promotions', 'is_featured')) {
                    $table->boolean('is_featured')->default(false)->after('status');
                    $table->index('is_featured');
                }
                if (! Schema::hasColumn('promotions', 'first_booking_only')) {
                    $table->boolean('first_booking_only')->default(false)->after('is_featured');
                }
                if (! Schema::hasColumn('promotions', 'allowed_days')) {
                    $table->json('allowed_days')->nullable()->after('first_booking_only');
                }
                if (! Schema::hasColumn('promotions', 'image_url')) {
                    $table->string('image_url')->nullable()->after('allowed_days');
                }
            });

            // Extend enums to include mobile-API values (type: free_hours; status: draft).
            DB::statement("ALTER TABLE `promotions` MODIFY COLUMN `type` ENUM('percentage','fixed','fixed_amount','free_hours') NOT NULL DEFAULT 'percentage'");
            DB::statement("ALTER TABLE `promotions` MODIFY COLUMN `status` ENUM('draft','active','inactive') NOT NULL DEFAULT 'active'");
            // Normalize legacy `fixed` values to `fixed_amount`.
            DB::statement("UPDATE `promotions` SET `type` = 'fixed_amount' WHERE `type` = 'fixed'");
            // Allow platform-wide promos (no owning club).
            DB::statement('ALTER TABLE `promotions` MODIFY COLUMN `club_id` BIGINT UNSIGNED NULL');
        }

        if (! Schema::hasTable('promotion_venue')) {
            Schema::create('promotion_venue', function (Blueprint $table) {
                $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
                $table->foreignId('venue_id')->constrained('venues')->cascadeOnDelete();
                $table->primary(['promotion_id', 'venue_id']);
            });
        }

        if (! Schema::hasTable('promotion_venue_category')) {
            Schema::create('promotion_venue_category', function (Blueprint $table) {
                $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
                $table->foreignId('venue_category_id')->constrained('venue_categories')->cascadeOnDelete();
                $table->primary(['promotion_id', 'venue_category_id']);
            });
        }

        if (! Schema::hasTable('promo_usages')) {
            Schema::create('promo_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
                $table->unsignedInteger('discount_amount')->default(0);
                $table->timestamps();

                $table->index(['promotion_id', 'user_id']);
                $table->index('booking_id');
            });
        }

        Schema::table('reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('reviews', 'venue_id')) {
                $table->foreignId('venue_id')->nullable()->after('club_id')->constrained('venues')->nullOnDelete();
                $table->index('venue_id');
            }
            if (! Schema::hasColumn('reviews', 'comment')) {
                $table->text('comment')->nullable()->after('body');
            }
            if (! Schema::hasColumn('reviews', 'pros')) {
                $table->json('pros')->nullable()->after('comment');
            }
            if (! Schema::hasColumn('reviews', 'cons')) {
                $table->json('cons')->nullable()->after('pros');
            }
            if (! Schema::hasColumn('reviews', 'helpful_count')) {
                $table->unsignedInteger('helpful_count')->default(0)->after('cons');
            }
        });

        if (! Schema::hasTable('review_helpful_votes')) {
            Schema::create('review_helpful_votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('review_id')->constrained('reviews')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['review_id', 'user_id']);
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('review_helpful_votes');

        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasColumn('reviews', 'venue_id')) {
                $table->dropForeign(['venue_id']);
                $table->dropColumn('venue_id');
            }
            foreach (['comment', 'pros', 'cons', 'helpful_count'] as $col) {
                if (Schema::hasColumn('reviews', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('promo_usages');

        Schema::table('promotions', function (Blueprint $table) {
            foreach (['is_featured', 'first_booking_only', 'allowed_days', 'image_url'] as $col) {
                if (Schema::hasColumn('promotions', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('promotions', 'venue_id')) {
                $table->dropForeign(['venue_id']);
                $table->dropColumn('venue_id');
            }
        });
    }
};
