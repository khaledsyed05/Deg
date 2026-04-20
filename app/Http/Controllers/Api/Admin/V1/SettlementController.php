<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\Settlement\CreateSettlementRequest;
use App\Http\Resources\SettlementResource;
use App\Models\Settlement;
use App\Repositories\Contracts\SettlementRepositoryInterface;
use App\Services\Payment\SettlementService;
use Illuminate\Http\JsonResponse;

class SettlementController extends Controller
{
    public function __construct(
        private SettlementRepositoryInterface $settlementRepo,
        private SettlementService $settlementService,
    ) {}

    /**
     * List settlements
     *
     * Returns a paginated list of all club settlements. Admin only.
     *
     * @response 200 {"success": true, "data": [{"id": 1, "club_id": 2, "amount": 250000, "status": "draft"}]}
     */
    public function index(): JsonResponse
    {
        $this->authorize('create', Settlement::class);

        $settlements = $this->settlementRepo->query()
            ->with('club')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => SettlementResource::collection($settlements),
        ]);
    }

    /**
     * Create a settlement
     *
     * Drafts a new settlement for a club covering a specified date range. Admin only.
     *
     * @bodyParam club_id integer required Club to settle. Example: 2
     * @bodyParam from_date string required Period start date (Y-m-d). Example: 2026-04-01
     * @bodyParam to_date string required Period end date (Y-m-d). Example: 2026-04-30
     *
     * @response 201 {"success": true, "data": {"id": 10, "club_id": 2, "amount": 250000, "status": "draft"}}
     * @response 403 {"message": "Forbidden"}
     */
    public function store(CreateSettlementRequest $request): JsonResponse
    {
        $this->authorize('create', Settlement::class);

        $settlement = $this->settlementService->draft(
            clubId: $request->club_id,
            periodFrom: $request->from_date,
            periodTo: $request->to_date,
            createdBy: $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'data' => new SettlementResource($settlement),
        ], 201);
    }

    /**
     * Get settlement details
     *
     * Returns full details for a single settlement. Admin only.
     *
     * @response 200 {"success": true, "data": {"id": 10, "club": {"id": 2, "name": "نادي النور"}, "amount": 250000}}
     * @response 403 {"message": "Forbidden"}
     */
    public function show(Settlement $settlement): JsonResponse
    {
        $this->authorize('view', $settlement);

        return response()->json([
            'success' => true,
            'data' => new SettlementResource($settlement->load('club')),
        ]);
    }
}
