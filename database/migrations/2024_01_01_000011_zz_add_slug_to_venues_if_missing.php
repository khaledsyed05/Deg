<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('venues') && ! Schema::hasColumn('venues', 'slug')) {
            Schema::table('venues', function (Blueprint $table) {
                $table->string('slug')->nullable()->unique()->after('id');
            });
        }
    }

    public function down(): void
    {
        // Intentionally no-op: slug is required in production.
    }
};
