<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\RequisitionSystem\Http\Requests\BudgetApprovalRequest;
use Modules\RequisitionSystem\Http\Requests\BudgetImportRequest;
use Modules\RequisitionSystem\Http\Requests\BudgetStoreRequest;
use Modules\RequisitionSystem\Http\Requests\BudgetUpdateRequest;
use Modules\RequisitionSystem\Models\Budget;
use Modules\RequisitionSystem\Models\BudgetApproval;
use Modules\RequisitionSystem\Models\BudgetLineItem;
use Modules\RequisitionSystem\Models\BudgetYear;
use Modules\RequisitionSystem\Models\CostCenter;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Services\BudgetCashFlowImportService;
use Modules\RequisitionSystem\Services\BudgetLogService;
use Modules\RequisitionSystem\Support\BudgetLogAction;
use Modules\RequisitionSystem\Support\BudgetWorkflow;
use Modules\RequisitionSystem\Support\GuardsBudgetAccess;
use RuntimeException;

class BudgetController extends Controller
{
    use GuardsBudgetAccess;

    public function __construct(
        private readonly BudgetLogService $logService,
        private readonly BudgetCashFlowImportService $importService
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertAuthenticated($user);

        $query = Budget::with([
            'costCenter',
            'budgetYear',
            'status',
            'stage',
            'lineItems.chartOfAccount',
        ])->latest('id');

        if ($this->userIsBudgetFinanceEditor($user)) {
            // Finance editors see all budgets.
        } elseif ($this->userIsBudgetCostCenterUser($user)) {
            $assignedIds = $this->userAssignedCostCenterIds($user);
            $query->whereIn('cost_center_id', $assignedIds);

            // Cost-center users may only filter within their own centers.
            if ($request->filled('cost_center_id')) {
                $requestedCostCenterId = $request->integer('cost_center_id');

                if (!$assignedIds->contains($requestedCostCenterId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only view budgets for your assigned cost center.',
                        'meta' => $this->budgetAccessMeta($user),
                    ], 403);
                }
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view budgets.',
            ], 403);
        }

        if ($request->filled('budget_year_id')) {
            $query->where('budget_year_id', $request->integer('budget_year_id'));
        }

        if ($request->filled('cost_center_id')) {
            $query->where('cost_center_id', $request->integer('cost_center_id'));
        }

        $budgets = $query->get()->map(
            fn (Budget $budget) => $this->formatBudgetResponse($budget, $user)
        );

        return response()->json([
            'success' => true,
            'data' => $budgets,
            'meta' => $this->budgetAccessMeta($user),
        ]);
    }

    /**
     * Active (in-force) budget line items for a cost center — used by POR forms.
     */
    public function operational(Request $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertAuthenticated($user);

        $validated = $request->validate([
            'cost_center_id' => 'required|integer|exists:porsql.cost_centers,id',
        ]);

        $costCenterId = (int) $validated['cost_center_id'];

        if (
            !$this->userIsBudgetFinanceEditor($user)
            && !$user->costCenters()->where('cost_centers.id', $costCenterId)->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You can only view the active budget for your assigned cost center.',
            ], 403);
        }

        $budget = Budget::query()
            ->operational()
            ->where('cost_center_id', $costCenterId)
            ->with(['lineItems.chartOfAccount', 'budgetYear', 'status', 'costCenter'])
            ->first();

        if (!$budget) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No active budget found for this cost center.',
            ]);
        }

        $lineItems = $budget->lineItems
            ->sortBy(fn (BudgetLineItem $item) => $item->chartOfAccount?->account_no)
            ->values()
            ->map(fn (BudgetLineItem $item) => [
                'id' => $item->id,
                'chart_of_account_id' => $item->chart_of_account_id,
                'amount' => $item->amount,
                'notes' => $item->notes,
                'chart_of_account' => $item->chartOfAccount
                    ? [
                        'id' => $item->chartOfAccount->id,
                        'account_no' => $item->chartOfAccount->account_no,
                        'description' => $item->chartOfAccount->description,
                        'parent_id' => $item->chartOfAccount->parent_id,
                    ]
                    : null,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'budget_id' => $budget->id,
                'cost_center_id' => $budget->cost_center_id,
                'budget_year' => $budget->budgetYear?->label,
                'status' => $budget->status?->name,
                'line_items' => $lineItems,
            ],
        ]);
    }

    public function import(BudgetImportRequest $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanUploadBudget($user);

        $validated = $request->validated();
        $costCenter = CostCenter::findOrFail($validated['cost_center_id']);
        $year = BudgetYear::findOrFail($validated['budget_year_id']);
        $statusName = $validated['status'] ?? 'Draft';
        $syncAccounts = array_key_exists('sync_accounts', $validated)
            ? (bool) $validated['sync_accounts']
            : true;

        try {
            $parsed = $this->importService->parse($request->file('file'));
            $budget = $this->importService->import(
                $costCenter,
                $year,
                $parsed['accounts'],
                $parsed['line_items'],
                $statusName,
                $validated['notes'] ?? null,
                $syncAccounts
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $budget->load([
            'costCenter',
            'budgetYear',
            'status',
            'stage',
            'lineItems.chartOfAccount',
        ]);

        $this->logService->record(
            $budget,
            $user,
            BudgetLogAction::CREATED,
            sprintf(
                'Budget uploaded from spreadsheet for %s (%s) with %d line items.',
                $costCenter->name,
                $year->label,
                count($parsed['line_items'])
            ),
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Budget imported for %s (%s) with %d accounts and %d line items.',
                $costCenter->name,
                $year->label,
                count($parsed['accounts']),
                count($parsed['line_items'])
            ),
            'data' => $this->formatBudgetResponse($budget, $user),
            'meta' => [
                'accounts_synced' => count($parsed['accounts']),
                'line_items' => count($parsed['line_items']),
                'total' => collect($parsed['line_items'])->sum('amount'),
            ],
        ], 201);
    }

    public function store(BudgetStoreRequest $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertAuthenticated($user);

        $validated = $request->validated();
        $year = BudgetYear::findOrFail($validated['budget_year_id']);

        if (!$this->userCanCreateBudgetForYear($year, $user)) {
            $message = $this->userIsBudgetCostCenterUser($user)
                && !$this->userIsBudgetFinanceEditor($user)
                && !$year->submissions_open
                ? sprintf(
                    'Budget submissions are not open for %s. You can create a budget only after the budget officer requests submissions for that year.',
                    $year->label
                )
                : 'You are not authorized to create a budget.';

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        if (
            $this->userIsBudgetCostCenterUser($user)
            && !$this->userIsBudgetFinanceEditor($user)
            && !$this->userAssignedCostCenterIds($user)->contains((int) $validated['cost_center_id'])
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You can only create budgets for your assigned cost center.',
            ], 403);
        }

        if (Budget::query()
            ->where('cost_center_id', $validated['cost_center_id'])
            ->where('budget_year_id', $validated['budget_year_id'])
            ->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'A budget already exists for this cost center and year.',
            ], 422);
        }

        $activeBudget = Budget::query()
            ->with(['status', 'budgetYear'])
            ->active()
            ->where('cost_center_id', $validated['cost_center_id'])
            ->latest('id')
            ->first();

        if ($activeBudget) {
            $yearLabel = $activeBudget->budgetYear?->label ?? 'another year';
            $statusName = $activeBudget->status?->name ?? 'in progress';

            return response()->json([
                'success' => false,
                'message' => "This cost center already has an active budget ({$statusName}) for {$yearLabel}. Finish or delete it before creating another.",
            ], 422);
        }

        $shouldSubmit = (bool) ($validated['submit'] ?? false);
        $lineItems = $validated['line_items'] ?? [];
        $activityComment = $validated['activity_comment'] ?? null;
        unset($validated['line_items'], $validated['submit'], $validated['activity_comment']);

        $budget = DB::connection('porsql')->transaction(function () use (
            $validated,
            $lineItems,
            $shouldSubmit,
            $user,
            $activityComment
        ) {
            BudgetWorkflow::applyDraftState($validated);
            $budget = Budget::create($validated);

            if (empty($lineItems)) {
                $previousYear = BudgetYear::query()
                    ->where('label', '<', BudgetYear::find($validated['budget_year_id'])?->label)
                    ->orderByDesc('label')
                    ->first();

                if ($previousYear) {
                    $previousBudget = Budget::with('lineItems')
                        ->where('cost_center_id', $validated['cost_center_id'])
                        ->where('budget_year_id', $previousYear->id)
                        ->first();

                    $lineItems = ($previousBudget?->lineItems ?? collect())
                        ->map(fn ($item) => [
                            'chart_of_account_id' => $item->chart_of_account_id,
                            'amount'              => (float) $item->amount,
                            'notes'               => null,
                        ])
                        ->all();
                }
            }

            $this->syncLineItems($budget, $lineItems);
            $budget->load(['costCenter', 'budgetYear', 'status', 'stage', 'lineItems.chartOfAccount']);

            $this->logService->recordCreated($budget, $user);

            if ($shouldSubmit) {
                $this->submitBudgetInternal($budget, $user, $activityComment);
            } elseif ($activityComment) {
                $this->logService->recordUpdated($budget, $user, $activityComment);
            }

            return $budget->fresh([
                'costCenter',
                'budgetYear',
                'status',
                'stage',
                'lineItems.chartOfAccount',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => $shouldSubmit
                ? 'Budget created and submitted successfully.'
                : 'Budget created successfully.',
            'data'    => $this->formatBudgetResponse($budget, $user),
        ], 201);
    }

    public function show(Budget $budget): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanViewBudget($budget, $user);

        $budget->load([
            'costCenter',
            'budgetYear',
            'status',
            'stage',
            'lineItems.chartOfAccount',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $this->formatBudgetResponse($budget, $user),
        ]);
    }

    public function comparison(Budget $budget): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanViewBudget($budget, $user);

        $budget->load(['budgetYear', 'lineItems.chartOfAccount', 'costCenter']);

        $previousYear = BudgetYear::query()
            ->where('label', '<', $budget->budgetYear->label)
            ->orderByDesc('label')
            ->first();

        $previousBudget = null;

        if ($previousYear) {
            $previousBudget = Budget::with(['budgetYear', 'lineItems.chartOfAccount'])
                ->where('cost_center_id', $budget->cost_center_id)
                ->where('budget_year_id', $previousYear->id)
                ->first();
        }

        $accountMap = [];

        foreach ($previousBudget?->lineItems ?? [] as $item) {
            $accountId = (int) $item->chart_of_account_id;
            $accountMap[$accountId] = [
                'chart_of_account_id' => $accountId,
                'account_no'          => $item->chartOfAccount?->account_no,
                'description'         => $item->chartOfAccount?->description,
                'previous'            => [
                    'amount' => (float) $item->amount,
                    'notes'  => $item->notes,
                ],
                'current' => [
                    'amount' => null,
                    'notes'  => null,
                ],
            ];
        }

        foreach ($budget->lineItems as $item) {
            $accountId = (int) $item->chart_of_account_id;

            if (!isset($accountMap[$accountId])) {
                $accountMap[$accountId] = [
                    'chart_of_account_id' => $accountId,
                    'account_no'          => $item->chartOfAccount?->account_no,
                    'description'         => $item->chartOfAccount?->description,
                    'previous'            => [
                        'amount' => null,
                        'notes'  => null,
                    ],
                    'current' => [
                        'amount' => null,
                        'notes'  => null,
                    ],
                ];
            }

            $accountMap[$accountId]['current'] = [
                'amount' => (float) $item->amount,
                'notes'  => $item->notes,
            ];
        }

        $rows = collect($accountMap)
            ->sortBy('account_no')
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data'    => [
                'budget' => $this->formatBudgetResponse($budget, $user),
                'previous_budget' => $previousBudget
                    ? $this->formatBudgetResponse(
                        $previousBudget->loadMissing(['status', 'stage', 'costCenter', 'budgetYear']),
                        $user
                    )
                    : null,
                'years' => [
                    'previous' => $previousYear?->label,
                    'current'  => $budget->budgetYear->label,
                ],
                'rows' => $rows,
            ],
        ]);
    }

    public function update(BudgetUpdateRequest $request, Budget $budget): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanEditBudgetLineItems($budget, $user);

        $validated = $request->validated();
        $shouldSubmit = (bool) ($validated['submit'] ?? false);
        $lineItems = $validated['line_items'] ?? null;
        $activityComment = $validated['activity_comment'] ?? null;
        unset($validated['line_items'], $validated['submit'], $validated['activity_comment']);

        if ($shouldSubmit && !$this->userCanSubmitBudget($budget, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to submit this budget.',
            ], 403);
        }

        $budget = DB::connection('porsql')->transaction(function () use (
            $budget,
            $validated,
            $lineItems,
            $shouldSubmit,
            $user,
            $activityComment
        ) {
            if (array_key_exists('notes', $validated)) {
                $budget->update(['notes' => $validated['notes']]);
            }

            if (is_array($lineItems)) {
                $this->syncLineItems($budget, $lineItems);
            }

            $budget->load(['costCenter', 'budgetYear', 'status', 'stage', 'lineItems.chartOfAccount']);
            $this->logService->recordUpdated($budget, $user, $activityComment);

            if ($shouldSubmit) {
                $this->submitBudgetInternal($budget, $user, $activityComment);
            }

            return $budget->fresh([
                'costCenter',
                'budgetYear',
                'status',
                'stage',
                'lineItems.chartOfAccount',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => $shouldSubmit
                ? 'Budget submitted successfully.'
                : 'Budget updated successfully.',
            'data'    => $this->formatBudgetResponse($budget, $user),
        ]);
    }

    public function destroy(Budget $budget): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanEditBudgetLineItems($budget, $user);

        if (!in_array($budget->status?->name, ['Draft'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft budgets can be deleted.',
            ], 422);
        }

        $budget->delete();

        return response()->json([
            'success' => true,
            'message' => 'Budget deleted successfully.',
        ]);
    }

    public function submit(BudgetApprovalRequest $request, Budget $budget): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertAuthenticated($user);

        if (!$this->userCanSubmitBudget($budget, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to submit this budget.',
            ], 403);
        }

        $this->submitBudgetInternal($budget, $user, $request->validated('comments'));

        $budget->refresh()->load([
            'costCenter',
            'budgetYear',
            'status',
            'stage',
            'lineItems.chartOfAccount',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Budget submitted successfully.',
            'data'    => $this->formatBudgetResponse($budget, $user),
        ]);
    }

    public function approve(BudgetApprovalRequest $request, Budget $budget): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanApproveBudget($budget, $user);

        $actingStageId = BudgetWorkflow::matchingUserStageId($budget, $user);
        $comments = $request->validated('comments');
        $stageName = Stage::find($actingStageId)?->name;

        DB::connection('porsql')->transaction(function () use ($budget, $user, $comments, $actingStageId) {
            BudgetApproval::create([
                'budget_id' => $budget->id,
                'user_id'   => $user->id,
                'stage_id'  => $actingStageId,
                'status'    => 'approved',
                'comments'  => $comments,
                'signed_at' => now(),
            ]);

            BudgetWorkflow::advanceAfterApproval($budget->refresh(), $actingStageId);
        });

        $budget->refresh()->load([
            'costCenter',
            'budgetYear',
            'status',
            'stage',
            'lineItems.chartOfAccount',
        ]);

        $this->logService->recordApprovalDecision(
            $budget,
            $user,
            BudgetLogAction::APPROVED,
            $comments,
            $stageName
        );

        return response()->json([
            'success' => true,
            'message' => 'Budget approved successfully.',
            'data'    => $this->formatBudgetResponse($budget, $user),
        ]);
    }

    public function reject(BudgetApprovalRequest $request, Budget $budget): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanApproveBudget($budget, $user);

        $actingStageId = BudgetWorkflow::matchingUserStageId($budget, $user);
        $comments = $request->validated('comments');
        $stageName = Stage::find($actingStageId)?->name;

        DB::connection('porsql')->transaction(function () use ($budget, $user, $comments, $actingStageId) {
            BudgetApproval::create([
                'budget_id' => $budget->id,
                'user_id'   => $user->id,
                'stage_id'  => $actingStageId,
                'status'    => 'rejected',
                'comments'  => $comments,
                'signed_at' => now(),
            ]);

            BudgetWorkflow::applyRejection($budget->refresh());
        });

        $budget->refresh()->load([
            'costCenter',
            'budgetYear',
            'status',
            'stage',
            'lineItems.chartOfAccount',
        ]);

        $this->logService->recordApprovalDecision(
            $budget,
            $user,
            BudgetLogAction::REJECTED,
            $comments,
            $stageName
        );

        return response()->json([
            'success' => true,
            'message' => 'Budget rejected successfully.',
            'data'    => $this->formatBudgetResponse($budget, $user),
        ]);
    }

    public function requestReview(BudgetApprovalRequest $request, Budget $budget): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanApproveBudget($budget, $user);

        $actingStageId = BudgetWorkflow::matchingUserStageId($budget, $user);
        $comments = $request->validated('comments');
        $stageName = Stage::find($actingStageId)?->name;

        DB::connection('porsql')->transaction(function () use ($budget, $user, $comments, $actingStageId) {
            BudgetApproval::create([
                'budget_id' => $budget->id,
                'user_id'   => $user->id,
                'stage_id'  => $actingStageId,
                'status'    => 'cost_center_review',
                'comments'  => $comments,
                'signed_at' => now(),
            ]);

            BudgetWorkflow::applyCostCenterReview($budget->refresh());
        });

        $budget->refresh()->load([
            'costCenter',
            'budgetYear',
            'status',
            'stage',
            'lineItems.chartOfAccount',
        ]);

        $this->logService->recordCostCenterReviewRequest(
            $budget,
            $user,
            $comments,
            $stageName
        );

        return response()->json([
            'success' => true,
            'message' => 'Budget sent back to cost center for review.',
            'data'    => $this->formatBudgetResponse($budget, $user),
        ]);
    }

    public function activate(BudgetApprovalRequest $request, Budget $budget): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanActivateBudget($budget, $user);

        $comments = $request->validated('comments');

        DB::connection('porsql')->transaction(function () use ($budget) {
            BudgetWorkflow::applyActivation($budget->refresh());
        });

        $budget->refresh()->load([
            'costCenter',
            'budgetYear',
            'status',
            'stage',
            'lineItems.chartOfAccount',
        ]);

        $this->logService->recordActivated($budget, $user, $comments);

        return response()->json([
            'success' => true,
            'message' => 'Budget activated successfully.',
            'data'    => $this->formatBudgetResponse($budget, $user),
        ]);
    }

    public function deactivate(BudgetApprovalRequest $request, Budget $budget): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanDeactivateBudget($budget, $user);

        $comments = $request->validated('comments');

        BudgetWorkflow::applyDeactivation($budget);

        $budget->refresh()->load([
            'costCenter',
            'budgetYear',
            'status',
            'stage',
            'lineItems.chartOfAccount',
        ]);

        $this->logService->recordDeactivated($budget, $user, $comments);

        return response()->json([
            'success' => true,
            'message' => 'Budget deactivated successfully.',
            'data'    => $this->formatBudgetResponse($budget, $user),
        ]);
    }

    private function submitBudgetInternal(Budget $budget, $user, ?string $comments = null): void
    {
        BudgetWorkflow::applySubmitState($budget);

        $budget->refresh()->load(['costCenter', 'budgetYear']);
        $this->logService->recordSubmission($budget, $user, $comments);
    }

    private function syncLineItems(Budget $budget, array $lineItems): void
    {
        $budget->lineItems()->delete();

        foreach ($lineItems as $item) {
            BudgetLineItem::create([
                'budget_id'           => $budget->id,
                'chart_of_account_id' => $item['chart_of_account_id'],
                'amount'              => $item['amount'],
                'notes'               => $item['notes'] ?? null,
            ]);
        }
    }

    private function formatBudgetResponse(Budget $budget, $user): Budget
    {
        $budget->loadMissing(['status', 'stage', 'costCenter', 'budgetYear']);

        $stageDecision = $user
            ? $this->findBudgetStageDecision($budget, $user)
            : null;

        $budget->setAttribute(
            'can_edit',
            $this->userCanEditBudgetLineItems($budget, $user)
        );
        $budget->setAttribute(
            'can_submit',
            $this->userCanSubmitBudget($budget, $user)
        );
        $budget->setAttribute(
            'show_approval_actions',
            $this->userCanViewBudgetApprovalActions($budget, $user)
        );
        $budget->setAttribute(
            'can_approve',
            $this->canUserApproveBudget($budget, $user)
        );
        $budget->setAttribute(
            'can_activate',
            $this->userCanActivateBudget($budget, $user)
        );
        $budget->setAttribute(
            'can_deactivate',
            $this->userCanDeactivateBudget($budget, $user)
        );
        $budget->setAttribute(
            'user_stage_action',
            $stageDecision?->status
        );

        $budget->loadMissing('pipeline.stages');

        return $budget;
    }
}
