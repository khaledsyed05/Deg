<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'venues_lat_lng_idx';

    public function up(): void
    {
        if ($this->indexExists(self::INDEX_NAME)) {
            return;
        }

        Schema::table('venues', function (Blueprint $table): void {
            $table->index(['latitude', 'longitude'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        if (! $this->indexExists(self::INDEX_NAME)) {
            return;
        }

        Schema::table('venues', function (Blueprint $table): void {
            $table->dropIndex(self::INDEX_NAME);
        });
    }

    private function indexExists(string $name): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $rows = Schema::getConnection()->select(
                "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='venues' AND name=?",
                [$name],
            );

            return ! empty($rows);
        }

        // mysql / mariadb
        $rows = Schema::getConnection()->select(
            'SHOW INDEX FROM venues WHERE Key_name = ?',
            [$name],
        );

        return ! empty($rows);
    }
};
