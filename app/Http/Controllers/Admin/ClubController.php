<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Repositories\Contracts\ClubRepositoryInterface;
use App\Services\Club\ClubApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClubController extends Controller
{
    public function __construct(
        private ClubRepositoryInterface $clubRepo,
        private ClubApprovalService $approvalService,
    ) {}

    public function index(): Response
    {
        $clubs = $this->clubRepo->findPendingApproval()
            ->map(fn ($club) => [
                'id' => $club->id,
                'name' => $club->getTranslation('name', 'ar'),
                'phone' => $club->phone ?? null,
                'created_at' => $club->created_at,
                'status' => $club->status,
            ]);

        return Inertia::render('Admin/Clubs/Index', [
            'clubs' => $clubs,
        ]);
    }

    public function approve(Request $request, Club $club): RedirectResponse
    {
        $this->approvalService->approve($club, $request->user()->id);

        return redirect()->route('admin.clubs.index')
            ->with('success', 'تم قبول النادي بنجاح');
    }

    public function reject(Request $request, Club $club): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $this->approvalService->reject($club, $request->reason);

        return redirect()->route('admin.clubs.index')
            ->with('success', 'تم رفض النادي');
    }
}
