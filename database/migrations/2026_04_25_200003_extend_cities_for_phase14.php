<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            if (! Schema::hasColumn('cities', 'country_id')) {
                $table->foreignId('country_id')->nullable()->after('id')
                    ->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('cities', 'is_visible')) {
                $table->boolean('is_visible')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('cities', 'is_popular')) {
                $table->boolean('is_popular')->default(false)->after('is_visible');
                $table->index('is_popular');
            }
            if (! Schema::hasColumn('cities', 'display_order')) {
                $table->integer('display_order')->default(0)->after('is_popular');
            }
            if (! Schema::hasColumn('cities', 'venues_count')) {
                $table->integer('venues_count')->default(0)->after('display_order');
            }
        });

        // Backfill country_id from state's country_id (portable: sub-select)
        DB::table('cities')
            ->whereNull('country_id')
            ->update([
                'country_id' => DB::raw('(SELECT country_id FROM states WHERE states.id = cities.state_id)'),
            ]);
        DB::table('cities')->where('is_active', true)->update(['is_visible' => true]);
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            foreach (['venues_count', 'display_order', 'is_popular', 'is_visible'] as $col) {
                if (Schema::hasColumn('cities', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('cities', 'country_id')) {
                $table->dropConstrainedForeignId('country_id');
            }
        });
    }
};
