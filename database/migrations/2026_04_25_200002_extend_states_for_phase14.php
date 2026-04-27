<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('states', function (Blueprint $table) {
            if (! Schema::hasColumn('states', 'type')) {
                $table->string('type', 50)->nullable()->after('state_code');
            }
            if (! Schema::hasColumn('states', 'is_visible')) {
                $table->boolean('is_visible')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('states', 'display_order')) {
                $table->integer('display_order')->default(0)->after('is_visible');
                $table->index(['country_id', 'is_visible']);
            }
        });

        DB::table('states')->where('is_active', true)->update(['is_visible' => true]);
    }

    public function down(): void
    {
        Schema::table('states', function (Blueprint $table) {
            foreach (['display_order', 'is_visible', 'type'] as $col) {
                if (Schema::hasColumn('states', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
