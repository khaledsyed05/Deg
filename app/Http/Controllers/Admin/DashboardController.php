<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\ClubRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepo,
        private ClubRepositoryInterface $clubRepo,
        private UserRepositoryInterface $userRepo,
    ) {}

    public function index(): Response
    {
        $stats = [
            'total_users' => $this->userRepo->query()->count(),
            'total_clubs' => $this->clubRepo->query()->count(),
            'pending_clubs' => $this->clubRepo->findPendingApproval()->count(),
            'total_bookings' => $this->bookingRepo->query()->count(),
            'confirmed_bookings' => $this->bookingRepo->query()->where('status', 'confirmed')->count(),
        ];

        $revenueByMonth = $this->bookingRepo->query()
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(6))
            ->selectRaw(
                config('database.default') === 'sqlite'
                    ? "strftime('%Y-%m', created_at) as month, SUM(total_price) as revenue"
                    : "DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_price) as revenue"
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'revenue_by_month' => $revenueByMonth,
        ]);
    }
}
