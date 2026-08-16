<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Budget;
use Modules\RequisitionSystem\Models\CostCenter;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Models\Status;
use Modules\RequisitionSystem\Models\Supplier;
use Modules\RequisitionSystem\Support\RequisitionWorkflow;

class RequisitionDashboardController extends Controller
{
    /**
     * Highest-to-lowest precedence used to pick a user's primary dashboard
     * role when they hold more than one (e.g. super-admin + requester).
     * roles()->first() is unordered, so a user can otherwise land on a
     * lower-privilege role depending on pivot row order.
     */
    private const ROLE_PRIORITY = [
        'super-admin',
        'president',
        'vice-president',
        'director-of-finance',
        'director-dean',
        'budget-officer',
        'purchase-officer',
        'requester',
    ];

    /**
     * GET /requisitions/dashboard-metrics
     */
    public function metrics(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $currentRole = $request->get('role') ?? $this->resolvePrimaryRole($user);
        $isSubmittedView = $request->get('view') === 'submitted';

        $query = Requisition::query();

        if ($isSubmittedView || !$this->userHasDashboardGlobalAccess($user, $currentRole)) {
            $assignedCostCenterIds = $user->costCenters()->pluck('cost_centers.id');

            if ($assignedCostCenterIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'role_context' => $currentRole,
                    'metrics' => $this->getEmptyMetricsResponse($currentRole),
                ]);
            }

            $query->whereIn('requisitions.cost_center_id', $assignedCostCenterIds);
        }

        $statusIds = $this->statusIdsByName();
        $pendingId = $statusIds['Pending'] ?? 0;
        $approvedId = $statusIds['Approved'] ?? 0;
        $rejectedId = $statusIds['Rejected'] ?? 0;
        $draftId = $statusIds['Draft'] ?? 0;

        $stageMapping = RequisitionWorkflow::roleStageMapping();
        $monthStart = Carbon::now()->startOfMonth()->toDateTimeString();

        if (!$isSubmittedView && array_key_exists($currentRole, $stageMapping)) {
            $targetStage = (int) $stageMapping[$currentRole];
            $targetSequence = RequisitionWorkflow::sequenceForStageId($targetStage) ?? 0;

            $metricsResult = $query->select(
                DB::raw("SUM(CASE WHEN requisitions.stage_id = {$targetStage} AND requisitions.status_id = {$pendingId} THEN 1 ELSE 0 END) as awaiting_my_action"),
                DB::raw("SUM(CASE WHEN COALESCE(requisitions.current_stage_sequence, 0) >= {$targetSequence} AND requisitions.status_id NOT IN ({$approvedId}, {$rejectedId}) THEN 1 ELSE 0 END) as in_pipeline"),
                DB::raw("SUM(CASE WHEN requisitions.status_id = {$approvedId} AND requisitions.updated_at >= '{$monthStart}' THEN 1 ELSE 0 END) as approved_this_month")
            )->first();

            $pendingSupplierRequests = Supplier::pending()->count();

            return response()->json([
                'success' => true,
                'role_context' => $currentRole,
                'metrics' => $this->buildMetricsPayload($metricsResult, true, $pendingSupplierRequests),
            ]);
        }

        $metricsResult = $query->select(
            DB::raw("SUM(CASE WHEN requisitions.status_id = {$pendingId} THEN 1 ELSE 0 END) as pending"),
            DB::raw("SUM(CASE WHEN requisitions.status_id = {$approvedId} THEN 1 ELSE 0 END) as approved"),
            DB::raw("SUM(CASE WHEN requisitions.status_id = {$rejectedId} THEN 1 ELSE 0 END) as rejected"),
            DB::raw("SUM(CASE WHEN requisitions.status_id = {$draftId} THEN 1 ELSE 0 END) as draft")
        )->first();

        return response()->json([
            'success' => true,
            'role_context' => $currentRole,
            'metrics' => $this->buildMetricsPayload($metricsResult, false),
        ]);
    }

    /**
     * GET /requisitions/recent
     */
    public function recent(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $currentRole = $request->get('role') ?? $this->resolvePrimaryRole($user);
        $isSubmittedView = $request->get('view') === 'submitted';

        $targetStageId = $isSubmittedView
            ? null
            : (RequisitionWorkflow::roleStageMapping()[$currentRole] ?? null);
        $previousStageId = $targetStageId !== null
            ? RequisitionWorkflow::previousStageIdForStage($targetStageId)
            : null;
        $targetSequence = $targetStageId !== null
            ? RequisitionWorkflow::sequenceForStageId($targetStageId)
            : null;

        $query = Requisition::with([
            'status',
            'stage',
            'approvals' => fn ($q) => $q->orderByDesc('signed_at'),
        ])
            ->select([
                'requisitions.*',
                'suppliers.name as dynamic_supplier_name',
            ])
            ->leftJoin('requisition_suppliers', function ($join) {
                $join->on('requisitions.id', '=', 'requisition_suppliers.requisition_id')
                    ->where('requisition_suppliers.is_recommended', true);
            })
            ->leftJoin('suppliers', 'requisition_suppliers.supplier_id', '=', 'suppliers.id');

        if ($targetStageId !== null && $targetSequence !== null) {
            $query->where(function ($q) use ($targetStageId, $targetSequence) {
                $q->where('requisitions.current_stage_sequence', '>=', $targetSequence)
                    ->orWhereHas('approvals', function ($approvalQuery) use ($targetStageId) {
                        $approvalQuery->where('stage_id', $targetStageId);
                    });
            });
        }

        if ($isSubmittedView || !$this->userHasDashboardGlobalAccess($user, $currentRole)) {
            $assignedCostCenterIds = $user->costCenters()->pluck('cost_centers.id');
            $query->whereIn('requisitions.cost_center_id', $assignedCostCenterIds);
        }

        $statusIds = $this->statusIdsByName();
        $terminalStatusIds = array_values(array_filter([
            $statusIds['Approved'] ?? null,
            $statusIds['Rejected'] ?? null,
        ]));

        $allRequisitions = $query->latest('requisitions.date_prepared')
            ->get()
            ->map(function ($requisition) use ($targetStageId, $previousStageId, $terminalStatusIds) {
                $base = [
                    'id' => $requisition->id,
                    'number' => $requisition->number,
                    'requisition_number' => $requisition->number,
                    'supplier_name' => $requisition->dynamic_supplier_name ?? 'No Supplier Linked',
                    'date_prepared' => $requisition->date_prepared,
                    'total' => (float) $requisition->total,
                    'status_name' => $requisition->status?->name,
                    'current_stage_name' => $requisition->stage?->name ?? $requisition->status?->name ?? 'Director review',
                ];

                if ($targetStageId !== null) {
                    $arrivalAt = $previousStageId === null
                        ? $requisition->date_prepared
                        : $requisition->approvals
                            ->first(fn ($a) => (int) $a->stage_id === $previousStageId && $a->status === 'approved')
                            ?->signed_at;

                    $ownAction = $requisition->approvals->first(fn ($a) => (int) $a->stage_id === $targetStageId);

                    $processingTimeHours = ($arrivalAt && $ownAction?->signed_at)
                        ? round(($ownAction->signed_at->timestamp - $arrivalAt->timestamp) / 3600, 2)
                        : null;

                    $approvalTimeHours = ($arrivalAt && $ownAction?->status === 'approved' && $ownAction->signed_at)
                        ? round(($ownAction->signed_at->timestamp - $arrivalAt->timestamp) / 3600, 2)
                        : null;

                    $base['processing_time_hours'] = $processingTimeHours;
                    $base['processing_time_display'] = $this->formatDurationHours($processingTimeHours);
                    $base['approval_time_hours'] = $approvalTimeHours;
                    $base['approval_time_display'] = $this->formatDurationHours($approvalTimeHours);

                    return $base;
                }

                $latestApprovedApproval = $requisition->approvals->first(fn ($a) => $a->status === 'approved');

                $processingTimeHours = in_array((int) $requisition->status_id, $terminalStatusIds, true)
                    ? round(($requisition->updated_at->timestamp - $requisition->date_prepared->timestamp) / 3600, 2)
                    : null;

                $approvalTimeHours = ($latestApprovedApproval?->signed_at && $requisition->date_prepared)
                    ? round(($latestApprovedApproval->signed_at->timestamp - $requisition->date_prepared->timestamp) / 3600, 2)
                    : null;

                $base['processing_time_hours'] = $processingTimeHours;
                $base['processing_time_display'] = $this->formatDurationHours($processingTimeHours);
                $base['approval_time_hours'] = $approvalTimeHours;
                $base['approval_time_display'] = $this->formatDurationHours($approvalTimeHours);

                return $base;
            });

        return response()->json([
            'success' => true,
            'data' => $allRequisitions,
        ]);
    }

    /**
     * Roles whose view of the cost-center summary is limited to the cost
     * center(s) they're personally assigned to, rather than every cost
     * center in the system.
     */
    private const COST_CENTER_SUMMARY_SCOPED_ROLES = [
        'requester',
        'director-dean',
    ];

    /**
     * GET /requisitions/summary-by-cost-center
     */
    public function summaryByCostCenter(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $currentRole = $request->get('role') ?? $this->resolvePrimaryRole($user);
        $isScoped = in_array($currentRole, self::COST_CENTER_SUMMARY_SCOPED_ROLES, true);

        $assignedCostCenterIds = null;

        if ($isScoped) {
            $assignedCostCenterIds = $user->costCenters()->pluck('cost_centers.id');

            if ($assignedCostCenterIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'role_context' => $currentRole,
                    'scope' => 'assigned',
                    'stages' => [],
                    'data' => [],
                    'totals' => ['by_stage' => [], 'grand_total' => 0],
                ]);
            }
        }

        $pipelineId = RequisitionWorkflow::defaultPipelineId();
        $stages = Stage::query()
            ->select('stages.id', 'stages.name')
            ->join('pipeline_stages', 'pipeline_stages.stage_id', '=', 'stages.id')
            ->where('pipeline_stages.pipeline_id', $pipelineId)
            ->orderBy('pipeline_stages.sequence')
            ->orderBy('stages.id')
            ->get();

        $counts = Requisition::query()
            ->select('cost_center_id', 'stage_id', DB::raw('COUNT(*) as requisition_count'))
            ->when($assignedCostCenterIds !== null, function ($query) use ($assignedCostCenterIds) {
                $query->whereIn('cost_center_id', $assignedCostCenterIds);
            })
            ->groupBy('cost_center_id', 'stage_id')
            ->get();

        $costCenters = CostCenter::whereIn('id', $counts->pluck('cost_center_id')->unique())
            ->orderBy('name')
            ->get(['id', 'name']);

        $data = $costCenters->map(function (CostCenter $costCenter) use ($counts, $stages) {
            $stageCounts = [];
            $total = 0;

            foreach ($stages as $stage) {
                $count = (int) $counts
                    ->where('cost_center_id', $costCenter->id)
                    ->where('stage_id', $stage->id)
                    ->sum('requisition_count');

                $stageCounts[$stage->id] = $count;
                $total += $count;
            }

            return [
                'cost_center_id' => $costCenter->id,
                'cost_center_name' => $costCenter->name,
                'total' => $total,
                'stages' => $stageCounts,
            ];
        })->values();

        $stageTotals = [];
        foreach ($stages as $stage) {
            $stageTotals[$stage->id] = (int) $counts->where('stage_id', $stage->id)->sum('requisition_count');
        }

        return response()->json([
            'success' => true,
            'role_context' => $currentRole,
            'scope' => $isScoped ? 'assigned' : 'all',
            'stages' => $stages,
            'data' => $data,
            'totals' => [
                'by_stage' => $stageTotals,
                'grand_total' => array_sum($stageTotals),
            ],
        ]);
    }

    /**
     * Longest span the caller may request in one call — keeps the per-day
     * response bounded regardless of what date_from/date_to they send.
     */
    private const BALANCE_OVER_TIME_MAX_DAYS = 366;

    /**
     * GET /requisitions/balance-over-time
     *
     * Cumulative "Closed" spend vs. the operational (Active) budget's
     * allocated total, per day, for one cost center or every cost center the
     * caller is assigned to. "Closed" is used as the spend signal because
     * that's the Purchase Officer's terminal "this was actually bought"
     * status — Approved only means it cleared the workflow, not that money
     * moved.
     */
    public function balanceOverTime(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'cost_center_id' => 'nullable|integer|exists:porsql.cost_centers,id',
        ]);

        $dateTo = Carbon::parse($validated['date_to'])->endOfDay();
        $dateFrom = Carbon::parse($validated['date_from'])->startOfDay();

        $spanDays = $dateFrom->diffInDays($dateTo) + 1;
        if ($spanDays > self::BALANCE_OVER_TIME_MAX_DAYS) {
            $dateFrom = $dateTo->copy()->subDays(self::BALANCE_OVER_TIME_MAX_DAYS - 1)->startOfDay();
        }

        $currentRole = $request->get('role') ?? $this->resolvePrimaryRole($user);
        $hasGlobalAccess = $this->userHasDashboardGlobalAccess($user, $currentRole);
        $requestedCostCenterId = $request->filled('cost_center_id') ? (int) $validated['cost_center_id'] : null;

        if ($requestedCostCenterId !== null) {
            if (!$hasGlobalAccess && !$user->costCenters()->where('cost_centers.id', $requestedCostCenterId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this cost center.',
                ], 403);
            }

            $costCenterIds = collect([$requestedCostCenterId]);
        } elseif ($hasGlobalAccess) {
            return response()->json([
                'success' => false,
                'message' => 'cost_center_id is required for this role.',
            ], 422);
        } else {
            $costCenterIds = $user->costCenters()->pluck('cost_centers.id');

            if ($costCenterIds->isEmpty()) {
                return response()->json(['success' => true, 'data' => []]);
            }
        }

        $allocatedByCostCenter = Budget::query()
            ->operational()
            ->whereIn('cost_center_id', $costCenterIds)
            ->withSum('lineItems as allocated', 'amount')
            ->get()
            ->keyBy('cost_center_id');

        $closedStatusId = RequisitionWorkflow::closedStatusId() ?? 0;

        // Spend that happened before the window, so the cumulative line
        // starts at the right height instead of resetting to 0.
        $priorSpend = Requisition::query()
            ->whereIn('cost_center_id', $costCenterIds)
            ->where('status_id', $closedStatusId)
            ->where('updated_at', '<', $dateFrom)
            ->groupBy('cost_center_id')
            ->select('cost_center_id', DB::raw('COALESCE(SUM(total), 0) as total'))
            ->pluck('total', 'cost_center_id');

        $dailySpend = Requisition::query()
            ->whereIn('cost_center_id', $costCenterIds)
            ->where('status_id', $closedStatusId)
            ->whereBetween('updated_at', [$dateFrom, $dateTo])
            ->select(
                'cost_center_id',
                DB::raw('DATE(updated_at) as spend_date'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('cost_center_id', 'spend_date')
            ->get()
            ->groupBy('cost_center_id');

        $costCenterNames = CostCenter::whereIn('id', $costCenterIds)->pluck('name', 'id');

        $dates = [];
        for ($cursor = $dateFrom->copy(); $cursor->lte($dateTo); $cursor->addDay()) {
            $dates[] = $cursor->toDateString();
        }

        $data = [];

        foreach ($costCenterIds as $costCenterId) {
            $allocated = (float) ($allocatedByCostCenter->get($costCenterId)?->allocated ?? 0);
            $cumulative = (float) ($priorSpend->get($costCenterId) ?? 0);
            $spendByDate = collect($dailySpend->get($costCenterId, []))->keyBy('spend_date');

            foreach ($dates as $date) {
                $cumulative += (float) ($spendByDate->get($date)?->total ?? 0);

                $data[] = [
                    'date' => $date,
                    'cost_center_id' => $costCenterId,
                    'cost_center_name' => $costCenterNames->get($costCenterId),
                    'spent_cumulative' => round($cumulative, 2),
                    'allocated' => round($allocated, 2),
                    'balance' => round($allocated - $cumulative, 2),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Picks the user's highest-precedence role per ROLE_PRIORITY, falling
     * back to whatever role they have if none of the ranked ones match.
     */
    protected function resolvePrimaryRole(User $user): string
    {
        $userRoles = $user->roles()->pluck('role_name');

        foreach (self::ROLE_PRIORITY as $role) {
            if ($userRoles->contains($role)) {
                return $role;
            }
        }

        return $userRoles->first() ?? 'requester';
    }

    /**
     * Dashboard queue views span departments for these workflow roles.
     * Separate from GuardsRequisitionEditing (finance/payroll only).
     */
    protected function userHasDashboardGlobalAccess(User $user, string $role): bool
    {
        $dashboardGlobalRoles = [
            'budget-officer',
            'vice-president',
            'director-of-finance',
            'purchase-officer',
            'payroll-officer',
            'super-admin',
        ];

        if (!in_array($role, $dashboardGlobalRoles, true)) {
            return false;
        }

        return $user->roles()->where('roles.role_name', $role)->exists()
            || $user->roles()->whereIn('roles.role_name', $dashboardGlobalRoles)->exists();
    }

    /**
     * @return array<string, int>
     */
    private function statusIdsByName(): array
    {
        return Status::query()
            ->whereIn('name', ['Draft', 'Pending', 'Approved', 'Rejected'])
            ->pluck('id', 'name')
            ->all();
    }

    private function buildMetricsPayload($result, bool $isWorkflowLayout, int $pendingSupplierRequests = 0): array
    {
        if ($isWorkflowLayout) {
            return [
                'awaiting_my_action' => (int) ($result->awaiting_my_action ?? 0),
                'in_pipeline' => (int) ($result->in_pipeline ?? 0),
                'approved_this_month' => (int) ($result->approved_this_month ?? 0),
                'supplier_requests' => $pendingSupplierRequests,
            ];
        }

        return [
            'pending' => (int) ($result->pending ?? 0),
            'approved' => (int) ($result->approved ?? 0),
            'rejected' => (int) ($result->rejected ?? 0),
            'draft' => (int) ($result->draft ?? 0),
        ];
    }

    private function getEmptyMetricsResponse(string $role): array
    {
        $workflowRoles = ['director-dean', 'budget-officer', 'vice-president', 'director-of-finance', 'purchase-officer'];

        if (in_array($role, $workflowRoles, true)) {
            return [
                'awaiting_my_action' => 0,
                'in_pipeline' => 0,
                'approved_this_month' => 0,
                'supplier_requests' => 0,
            ];
        }

        return ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'draft' => 0];
    }

    private function formatDurationHours(?float $hours): ?string
    {
        if ($hours === null) {
            return null;
        }

        $totalMinutes = (int) round($hours * 60);

        if ($totalMinutes < 60) {
            return $totalMinutes . ' minute' . ($totalMinutes === 1 ? '' : 's');
        }

        $days = intdiv($totalMinutes, 1440);
        $hoursRemainder = intdiv($totalMinutes % 1440, 60);
        $minutesRemainder = $totalMinutes % 60;

        if ($days > 0) {
            $parts = [$days . 'd'];
            if ($hoursRemainder > 0) {
                $parts[] = $hoursRemainder . 'h';
            }

            return implode(' ', $parts);
        }

        $parts = [$hoursRemainder . 'h'];
        if ($minutesRemainder > 0) {
            $parts[] = $minutesRemainder . 'm';
        }

        return implode(' ', $parts);
    }
}
