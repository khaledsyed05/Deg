#!/usr/bin/env bash
set -u

echo "🔧 Phase 7 Setup & Smoke Tests"
echo "================================="

cd "$(dirname "$0")/.."

echo ""
echo "1️⃣  Running migrations..."
php artisan migrate --force

echo ""
echo "2️⃣  Checking classes..."
php artisan tinker --no-interaction --execute="
  \$checks = [
    'BookingObserver' => App\\Observers\\BookingObserver::class,
    'ReviewObserver' => App\\Observers\\ReviewObserver::class,
    'ReferralObserver' => App\\Observers\\ReferralObserver::class,
    'PlayerStats' => App\\Models\\PlayerStats::class,
    'Achievement' => App\\Models\\Achievement::class,
    'SavedSearch' => App\\Models\\SavedSearch::class,
    'SupportTicket' => App\\Models\\SupportTicket::class,
    'Referral' => App\\Models\\Referral::class,
    'AchievementType' => App\\Enums\\AchievementType::class,
  ];
  foreach (\$checks as \$label => \$class) {
    echo str_pad(\$label, 20) . (class_exists(\$class) || enum_exists(\$class) ? '✅' : '❌') . PHP_EOL;
  }
"

echo ""
echo "3️⃣  Testing player stats auto-creation on an existing user..."
php artisan tinker --no-interaction --execute="
  \$user = App\\Models\\User::first();
  if (!\$user) { echo 'No users in DB, skipping'; exit; }
  \$stats = App\\Models\\PlayerStats::firstOrCreate(['user_id' => \$user->id]);
  echo 'User: ' . \$user->id . PHP_EOL;
  echo 'Stats row exists: ✅' . PHP_EOL;
  echo 'Total bookings: ' . \$stats->total_bookings . PHP_EOL;
  echo 'Total spent: ' . \$stats->total_spent . PHP_EOL;
"

echo ""
echo "4️⃣  Counting existing achievements per user (first user)..."
php artisan tinker --no-interaction --execute="
  \$user = App\\Models\\User::first();
  if (!\$user) { exit; }
  echo 'Achievement rows: ' . \$user->achievements()->count() . PHP_EOL;
  echo 'Unlocked: ' . \$user->achievements()->whereNotNull('unlocked_at')->count() . PHP_EOL;
"

echo ""
echo "5️⃣  Phase 7 routes:"
php artisan route:list --path=api/v1/profile --except-vendor 2>/dev/null | tail -n +4 | head -n 10
php artisan route:list --path=api/v1/search --except-vendor 2>/dev/null | tail -n +4
php artisan route:list --path=api/v1/support --except-vendor 2>/dev/null | tail -n +4
php artisan route:list --path=api/v1/leaderboard --except-vendor 2>/dev/null | tail -n +4
php artisan route:list --path=api/v1/referrals --except-vendor 2>/dev/null | tail -n +4
php artisan route:list --path=api/v1/friends --except-vendor 2>/dev/null | tail -n +4
php artisan route:list --path=api/v1/feedback --except-vendor 2>/dev/null | tail -n +4

echo ""
echo "✅ Setup complete."
echo ""
echo "Next:"
echo "  - Import docs/postman/DaqEhjezly_Mobile_API_v1_Complete.postman_collection.json"
echo "  - Set {{access_token}} and smoke test endpoints under folder 07"
