<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            if (! Schema::hasColumn('venues', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('status');
                $table->index('is_featured');
            }

            if (! Schema::hasColumn('venues', 'view_count')) {
                $table->unsignedInteger('view_count')->default(0)->after('is_featured');
                $table->index('view_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            if (Schema::hasColumn('venues', 'is_featured')) {
                $table->dropIndex(['is_featured']);
                $table->dropColumn('is_featured');
            }
            if (Schema::hasColumn('venues', 'view_count')) {
                $table->dropIndex(['view_count']);
                $table->dropColumn('view_count');
            }
        });
    }
};
