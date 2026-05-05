<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            if (! Schema::hasColumn('countries', 'currency_symbol')) {
                $table->string('currency_symbol', 10)->nullable()->after('currency');
            }
            if (! Schema::hasColumn('countries', 'flag_emoji')) {
                $table->string('flag_emoji', 10)->nullable()->after('currency_symbol');
            }
            if (! Schema::hasColumn('countries', 'is_visible')) {
                $table->boolean('is_visible')->default(false)->after('is_active');
                $table->index('is_visible');
            }
            if (! Schema::hasColumn('countries', 'display_order')) {
                $table->integer('display_order')->default(0)->after('is_visible');
            }
        });

        DB::table('countries')->where('is_active', true)->update(['is_visible' => true]);
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            foreach (['display_order', 'is_visible', 'flag_emoji', 'currency_symbol'] as $col) {
                if (Schema::hasColumn('countries', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
