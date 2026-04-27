<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('reason', [
                'inappropriate_content', 'false_information', 'safety_concern',
                'pricing_dispute', 'fake_venue', 'other',
            ]);
            $table->text('description');
            $table->json('evidence_urls')->nullable();
            $table->enum('status', ['pending', 'under_review', 'action_taken', 'dismissed'])
                ->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('venue_id');
            $table->unique(['venue_id', 'user_id'], 'unique_user_venue_report');
        });

        Schema::table('venues', function (Blueprint $table) {
            if (! Schema::hasColumn('venues', 'reports_count')) {
                $table->integer('reports_count')->default(0)->after('reviews_count');
            }
            if (! Schema::hasColumn('venues', 'is_flagged')) {
                $table->boolean('is_flagged')->default(false)->after('reports_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            foreach (['is_flagged', 'reports_count'] as $col) {
                if (Schema::hasColumn('venues', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('venue_reports');
    }
};
