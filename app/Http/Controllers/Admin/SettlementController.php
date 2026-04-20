<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Repositories\Contracts\ClubRepositoryInterface;
use App\Repositories\Contracts\SettlementRepositoryInterface;
use App\Services\Payment\SettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettlementController extends Controller
{
    public function __construct(
        private SettlementRepositoryInterface $settlementRepo,
        private SettlementService $settlementService,
        private ClubRepositoryInterface $clubRepo,
    ) {}

    public function index(): Response
    {
        $settlements = $this->settlementRepo->query()
            ->with('club:id,name')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn ($s) => [
                'id' => $s->id,
                'club' => $s->club ? ['id' => $s->club->id, 'name' => $s->club->name] : null,
                'total_amount' => $s->total_amount,
                'status' => $s->status,
                'period_from' => $s->period_from?->toDateString(),
                'period_to' => $s->period_to?->toDateString(),
            ]);

        $clubs = $this->clubRepo->query()
            ->select('id', 'name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]);

        return Inertia::render('Admin/Settlements/Index', [
            'settlements' => $settlements,
            'clubs' => $clubs,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'club_id' => ['required', 'integer', 'exists:clubs,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $this->settlementService->draft(
            clubId: $request->club_id,
            periodFrom: $request->from_date,
            periodTo: $request->to_date,
            createdBy: $request->user()->id,
        );

        return redirect()->route('admin.settlements.index')
            ->with('success', 'تم إنشاء التسوية بنجاح');
    }

    public function show(Settlement $settlement): Response
    {
        $settlement->load('club:id,name');

        return Inertia::render('Admin/Settlements/Show', [
            'settlement' => [
                'id' => $settlement->id,
                'club' => $settlement->club ? ['id' => $settlement->club->id, 'name' => $settlement->club->name] : null,
                'total_amount' => $settlement->total_amount,
                'status' => $settlement->status,
                'period_from' => $settlement->period_from?->toDateString(),
                'period_to' => $settlement->period_to?->toDateString(),
                'created_at' => $settlement->created_at?->toISOString(),
            ],
        ]);
    }
}
