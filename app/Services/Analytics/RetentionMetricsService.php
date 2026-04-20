<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;

/**
 * Cohort-based retention analysis — Super Admin only (LOCK-008).
 */
class RetentionMetricsService
{
    /**
     * Weekly retention cohort: users who booked in week W and returned in week W+N.
     *
     * @return array<int, array{cohort_week: string, week_number: int, retained_users: int}>
     */
    public function weeklyRetention(int $weeks = 8): array
    {
        return DB::select("
            SELECT
                DATE_FORMAT(MIN(booking_date), '%Y-%u') AS cohort_week,
                FLOOR(DATEDIFF(MAX(booking_date), MIN(booking_date)) / 7) AS week_number,
                COUNT(DISTINCT user_id) AS retained_users
            FROM bookings
            WHERE status IN ('completed', 'confirmed')
              AND user_id IS NOT NULL
            GROUP BY user_id
            HAVING COUNT(*) > 1
            ORDER BY cohort_week, week_number
            LIMIT ?
        ", [$weeks * 100]);
    }
}
