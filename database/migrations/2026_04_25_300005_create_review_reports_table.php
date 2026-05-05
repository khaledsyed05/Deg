<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('reason', [
                'spam', 'offensive_language', 'false_review', 'personal_attack',
                'inappropriate_content', 'fake_review', 'other',
            ]);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'review_hidden', 'review_kept', 'review_deleted'])
                ->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['review_id', 'user_id'], 'unique_user_review_report');
            $table->index('status');
        });

        Schema::table('reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('reviews', 'reports_count')) {
                $table->integer('reports_count')->default(0);
            }
            if (! Schema::hasColumn('reviews', 'is_hidden')) {
                $table->boolean('is_hidden')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            foreach (['is_hidden', 'reports_count'] as $col) {
                if (Schema::hasColumn('reviews', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('review_reports');
    }
};
