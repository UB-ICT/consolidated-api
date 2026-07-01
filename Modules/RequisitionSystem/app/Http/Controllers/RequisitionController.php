<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Carbon;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Approval;
use Modules\RequisitionSystem\Models\Currency;
use Modules\RequisitionSystem\Models\Item;
use Modules\RequisitionSystem\Models\Pipeline;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Models\Supplier;
use Modules\RequisitionSystem\Http\Requests\RequisitionApprovalRequest;
use Modules\RequisitionSystem\Http\Requests\RequisitionPurchaseOrderRequest;
use Modules\RequisitionSystem\Http\Requests\RequisitionStoreRequest;
use Modules\RequisitionSystem\Notifications\RequisitionSubmittedNotification;
use Modules\RequisitionSystem\Services\RequisitionLogService;
use Modules\RequisitionSystem\Support\GuardsRequisitionApproval;
use Modules\RequisitionSystem\Support\GuardsRequisitionCancellation;
use Modules\RequisitionSystem\Support\GuardsRequisitionEditing;
use Modules\RequisitionSystem\Support\GuardsRequisitionPurchaseOrder;
use Modules\RequisitionSystem\Support\RequisitionLogAction;
use Modules\RequisitionSystem\Support\RequisitionSupplierQuoteRules;
use Modules\RequisitionSystem\Support\RequisitionWorkflow;

class RequisitionController extends Controller
{
    use GuardsRequisitionEditing;
    use GuardsRequisitionApproval;
    use GuardsRequisitionCancellation;
    use GuardsRequisitionPurchaseOrder;

    public function __construct(
        private readonly RequisitionLogService $logService
    ) {}

    /**
     * List all requisitions with their attached suppliers & line items.
     * * Supports: 
     * - Sorting: Descending by default (latest)
     * - Filtering: By priority (?priority=urgent)
     * - Scoping: By authenticated user's cost centers (?scope=cost_center)
     * * Budget Officer, VP, Director of Finance, and Payroll Officer bypass isolation.
     */
    public function index(Request $request)
    {
        $query = Requisition::with(['suppliers.status', 'items', 'costCenter', 'stage', 'status', 'attachments.supplier.status']);
        if ($request->get('scope') === 'cost_center') {
            /** @var \Modules\Auth\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            // 1. Get the current role context (allows overrides from Postman/Frontend query strings too!)
            $currentRole = $request->get('role') ?? $user->roles()->first()?->role_name ?? 'requester';

            // 2. Pass BOTH the user object and the role string here ✅
            if (!$this->userHasGlobalRequisitionAccess($user, $currentRole)) {
                $assignedCostCenterIds = $user->costCenters()->pluck('cost_centers.id');

                if ($assignedCostCenterIds->isEmpty()) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized cost center access.'], 403);
                }

                $query->whereIn('cost_center_id', $assignedCostCenterIds);
            }
        }

        if ($request->has('cost_center_id')) {
            $query->where('cost_center_id', $request->get('cost_center_id'));
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('status_id')) {
            $query->where('status_id', $request->integer('status_id'));
        }

        if ($request->has('is_recurring')) {
            $query->where('is_recurring', $request->boolean('is_recurring'));
        }

        if ($request->get('filter_alerts') === 'upcoming') {
            $query->scopeUpcomingReminders(30);
        }

        $requisitions = $query->latest()->get();
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'count'   => $requisitions->count(),
            'data'    => $requisitions->map(
                fn(Requisition $requisition) => $this->formatRequisitionResponse($requisition, $user)
            ),
        ]);
    }

    public function store(RequisitionStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $shouldSubmit = $request->shouldSubmit();
        unset($validated['submit']);

        $requisition = DB::connection('porsql')->transaction(function () use ($validated, $shouldSubmit, $user) {
            $requisition = $this->persistRequisition($validated, null, $shouldSubmit, $user);
            $this->syncSuppliers($requisition, $validated['suppliers'] ?? []);

            return $requisition;
        });

        if ($shouldSubmit) {
            $this->logService->recordSubmission($requisition, $user);
            $this->notifyRequisitionSubmitted($requisition, $user);
        } else {
            $this->logService->recordCreation($requisition, $user);
        }

        return response()->json([
            'success' => true,
            'message' => $shouldSubmit
                ? 'Requisition submitted successfully.'
                : 'Requisition saved successfully.',
            'data' => $this->formatRequisitionResponse($requisition->refresh(), $user),
        ], 201);
    }

    public function show(Requisition $requisition): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'data' => $this->formatRequisitionResponse(
                $requisition->load(['suppliers.status', 'items', 'costCenter', 'stage', 'status', 'attachments.supplier.status']),
                $user
            ),
        ]);
    }

    public function update(
        RequisitionStoreRequest $request,
        Requisition $requisition
    ): JsonResponse {
        $validated = $request->validated();
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        $shouldSubmit = $request->shouldSubmit();
        unset($validated['submit']);

        $this->assertCostCenterCanEdit($requisition->load('status'), $user);
        $this->assertLineItemsNotAdded($requisition, $validated['items'] ?? [], $user);

        $before = $requisition->replicate();
        $before->setRelation('status', $requisition->status);
        $previousItems = $requisition->items()->get();
        $activityComment = $validated['activity_comment'] ?? null;
        unset($validated['activity_comment']);

        $requisition = DB::connection('porsql')->transaction(function () use ($validated, $requisition, $shouldSubmit) {
            $requisition = $this->persistRequisition($validated, $requisition, $shouldSubmit);
            $this->syncSuppliers($requisition, $validated['suppliers'] ?? []);

            return $requisition;
        });

        $requisition->load('status');

        if ($shouldSubmit) {
            $changeSummary = $this->logService->summarizeChanges(
                $before,
                $validated,
                $previousItems
            );

            $this->logService->recordSubmission(
                $requisition,
                $user,
                $activityComment,
                $changeSummary
            );
            $this->notifyRequisitionSubmitted($requisition, $user);
        } else {
            $this->logService->recordUpdate(
                $requisition,
                $user,
                $before,
                $validated,
                $previousItems,
                $activityComment
            );
        }

        return response()->json([
            'success' => true,
            'message' => $shouldSubmit
                ? 'Requisition submitted successfully.'
                : 'Requisition updated successfully.',
            'data' => $this->formatRequisitionResponse($requisition->refresh(), $user),
        ]);
    }

    public function approve(
        RequisitionApprovalRequest $request,
        Requisition $requisition
    ): JsonResponse {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        $this->assertUserCanApprove($requisition, $user);

        $actingStageId = $this->matchingUserStageId($requisition, $user);

        if (!$actingStageId) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to the current approval stage.',
            ], 403);
        }

        $comments = $request->validated('comments');
        $stageName = Stage::find($actingStageId)?->name;

        DB::connection('porsql')->transaction(function () use ($requisition, $user, $comments, $actingStageId) {
            Approval::create([
                'requisition_id' => $requisition->id,
                'user_id'        => $user->id,
                'stage_id'       => $actingStageId,
                'status'         => 'approved',
                'comments'       => $comments,
                'signed_at'      => now(),
            ]);

            // ✨ Let the dedicated workflow engine handle the database updates and user sync cleanly
            RequisitionWorkflow::advanceAfterApproval(
                $requisition->refresh(),
                $actingStageId
            );
        });

        $requisition->refresh()->load(['suppliers.status', 'items', 'costCenter', 'stage', 'status']);

        $this->logService->recordApprovalDecision(
            $requisition,
            $user,
            RequisitionLogAction::APPROVED,
            $comments,
            $stageName
        );

        return response()->json([
            'success' => true,
            'message' => 'Requisition approved successfully.',
            'data'    => $this->formatRequisitionResponse($requisition, $user),
        ]);
    }

    public function reject(
        RequisitionApprovalRequest $request,
        Requisition $requisition
    ): JsonResponse {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        $this->assertUserCanApprove($requisition, $user);

        $actingStageId = $this->matchingUserStageId($requisition, $user);

        if (!$actingStageId) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to the current approval stage.',
            ], 403);
        }

        $comments = $request->validated('comments');
        $stageName = Stage::find($actingStageId)?->name;

        DB::connection('porsql')->transaction(function () use ($requisition, $user, $comments, $actingStageId) {
            Approval::create([
                'requisition_id' => $requisition->id,
                'user_id'        => $user->id,
                'stage_id'       => $actingStageId,
                'status'         => 'rejected',
                'comments'       => $comments,
                'signed_at'      => now(),
            ]);

            RequisitionWorkflow::applyRejection($requisition->refresh());
        });

        $requisition->refresh()->load(['suppliers.status', 'items', 'costCenter', 'stage', 'status']);

        $this->logService->recordApprovalDecision(
            $requisition,
            $user,
            RequisitionLogAction::REJECTED,
            $comments,
            $stageName
        );

        return response()->json([
            'success' => true,
            'message' => 'Requisition rejected successfully.',
            'data'    => $this->formatRequisitionResponse($requisition, $user),
        ]);
    }

    public function requestReview(
        RequisitionApprovalRequest $request,
        Requisition $requisition
    ): JsonResponse {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        $this->assertUserCanApprove($requisition, $user);

        $actingStageId = $this->matchingUserStageId($requisition, $user);

        if (!$actingStageId) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to the current approval stage.',
            ], 403);
        }

        $comments = $request->validated('comments');
        $stageName = Stage::find($actingStageId)?->name;

        DB::connection('porsql')->transaction(function () use ($requisition, $user, $comments, $actingStageId) {
            Approval::create([
                'requisition_id' => $requisition->id,
                'user_id'        => $user->id,
                'stage_id'       => $actingStageId,
                'status'         => 'cost_center_review',
                'comments'       => $comments,
                'signed_at'      => now(),
            ]);

            RequisitionWorkflow::applyCostCenterReview($requisition->refresh());
        });

        $requisition->refresh()->load(['suppliers.status', 'items', 'costCenter', 'stage', 'status']);

        $this->logService->recordCostCenterReviewRequest(
            $requisition,
            $user,
            $comments,
            $stageName
        );

        return response()->json([
            'success' => true,
            'message' => 'Requisition sent back to cost center for review.',
            'data'    => $this->formatRequisitionResponse($requisition, $user),
        ]);
    }

    public function cancel(
        RequisitionApprovalRequest $request,
        Requisition $requisition
    ): JsonResponse {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        $this->assertUserCanCancel($requisition, $user);

        $comments = $request->validated('comments');

        RequisitionWorkflow::applyCancellation($requisition->refresh());

        $requisition->refresh()->load(['suppliers.status', 'items', 'costCenter', 'stage', 'status']);

        $this->logService->recordCancellation($requisition, $user, $comments);

        return response()->json([
            'success' => true,
            'message' => 'Requisition cancelled successfully.',
            'data'    => $this->formatRequisitionResponse($requisition, $user),
        ]);
    }

    public function updatePurchaseOrderNumber(
        RequisitionPurchaseOrderRequest $request,
        Requisition $requisition
    ): JsonResponse {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        $this->assertCanUpdatePurchaseOrderNumber($requisition, $user);

        $purchaseOrderNumber = $request->validated('purchase_order_number');
        $previousValue = $requisition->purchase_order_number;

        $requisition->update([
            'purchase_order_number' => $purchaseOrderNumber,
        ]);

        if ((string) $previousValue !== (string) $purchaseOrderNumber) {
            $this->logService->recordPurchaseOrderNumberUpdate(
                $requisition,
                $user,
                $purchaseOrderNumber,
                $previousValue
            );
        }

        $requisition->refresh()->load(['suppliers.status', 'items', 'costCenter', 'stage', 'status']);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order number updated successfully.',
            'data'    => $this->formatRequisitionResponse($requisition, $user),
        ]);
    }

    public function destroy(Requisition $requisition): JsonResponse
    {
        $requisition->delete();

        return response()->json([
            'success' => true,
            'message' => 'Requisition deleted successfully.',
        ]);
    }

    /**
     * Notify whoever holds the role responsible for the requisition's current
     * stage that it now needs their attention.
     */
    private function notifyRequisitionSubmitted(Requisition $requisition, ?User $submitter): void
    {
        $role = RequisitionWorkflow::requiredRoleForStageId((int) $requisition->stage_id);

        if ($role === null) {
            return;
        }

        $recipientsQuery = User::whereHas('roles', fn($query) => $query->where('role_name', $role));

        // Cost-center-scoped roles (e.g. director-dean) only review their own
        // department; global roles (budget-officer, VP, etc.) see everything.
        // user_cost_center lives on the 'porsql' connection (different schema
        // than User's 'pgsql' connection), so it can't be joined via whereHas.
        if (!in_array($role, $this->globalRequisitionRoles(), true)) {
            $costCenterUserIds = DB::connection('porsql')
                ->table('user_cost_center')
                ->where('cost_center_id', $requisition->cost_center_id)
                ->pluck('user_id');

            $recipientsQuery->whereIn('id', $costCenterUserIds);
        }

        $recipients = $recipientsQuery->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new RequisitionSubmittedNotification($requisition, $submitter));
        }
    }

    private function formatRequisitionResponse(Requisition $requisition, $user): Requisition
    {
        $requisition->loadMissing('status', 'stage');

        $requisition->setAttribute(
            'is_editable',
            $this->isEditableByCostCenter($requisition, $user)
        );

        $stageDecision = $user
            ? $this->findUserStageDecision($requisition, $user)
            : null;

        $requisition->setAttribute(
            'show_approval_actions',
            $this->userCanViewApprovalActions($requisition, $user)
        );

        $requisition->setAttribute(
            'can_approve',
            $this->canUserApproveRequisition($requisition, $user)
        );

        $requisition->setAttribute(
            'user_stage_action',
            $stageDecision?->status
        );

        $requisition->setAttribute(
            'can_edit_purchase_order_number',
            $this->canEditPurchaseOrderNumber($requisition, $user)
        );

        $requisition->setAttribute(
            'can_cancel',
            $this->userCanCancelRequisition($requisition, $user)
        );

        $pipeline = Pipeline::with('stages')
            ->find(RequisitionWorkflow::defaultPipelineId());

        if ($pipeline) {
            $requisition->setRelation('pipeline', $pipeline);
        }

        return $requisition;
    }

    private function persistRequisition(
        array $data,
        ?Requisition $requisition = null,
        bool $shouldSubmit = false,
        ?\Modules\Auth\Models\User $creator = null
    ): Requisition {
        $items = $data['items'];
        unset($data['items'], $data['suppliers'], $data['submit']);

        $data['total'] = collect($items)->sum(
            fn(array $item) => ($item['quantity'] ?? 0) * ($item['unit_cost'] ?? 0)
        );

        $data['number'] = $data['number']
            ?? $this->generateRequisitionNumber();

        if (!$requisition) {
            // A creator who themselves holds an approver role doesn't need their
            // own form routed through stages below their authority -- it starts
            // (and, if rejected, returns to) their own stage instead of Draft.
            $originStageId = $creator ? RequisitionWorkflow::originStageIdForUser($creator) : null;
            $data['created_by'] = $creator?->id;
            $data['origin_stage_id'] = $originStageId;

            if ($shouldSubmit) {
                RequisitionWorkflow::applySubmitState(
                    $data,
                    null,
                    RequisitionWorkflow::startStageIdFromOrigin($originStageId)
                );
            } else {
                RequisitionWorkflow::applyDraftState($data);
            }
        } elseif ($shouldSubmit) {
            if ($requisition->status?->name === 'Cost Center Review') {
                RequisitionWorkflow::applyResubmitFromCostCenterReview($data, $requisition);
            } elseif ($requisition->status?->name === 'Rejected') {
                RequisitionWorkflow::applyResubmitFromRejection($data, $requisition);
            } else {
                // First-time submit of a previously-saved Draft: still honor
                // the creator's origin stage, captured back when it was created.
                RequisitionWorkflow::applySubmitState(
                    $data,
                    null,
                    RequisitionWorkflow::startStageIdFromOrigin($requisition->origin_stage_id)
                );
            }
        } else {
            unset($data['status_id'], $data['stage_id'], $data['current_stage_sequence']);
        }

        $data['currency_id'] = $data['currency_id']
            ?? Currency::query()->value('id');

        if (!$data['is_recurring']) {
            $data['reminder_date'] = null;
        }

        if ($requisition) {
            $requisition->update($data);
            $requisition->items()->delete();
        } else {
            $requisition = Requisition::create($data);
        }

        // Populate user_stages for whoever is responsible for the submitted stage.
        if ($shouldSubmit) {
            $submittedStageId = (int) $requisition->stage_id;
            $targetRole = RequisitionWorkflow::requiredRoleForStageId($submittedStageId);

            if ($targetRole !== null) {
                $userIds = DB::connection('pgsql')
                    ->table('user_roles')
                    ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                    ->where('roles.role_name', $targetRole)
                    ->pluck('user_roles.user_id')
                    ->toArray();

                foreach ($userIds as $userId) {
                    DB::connection('porsql')->table('user_stages')->updateOrInsert(
                        ['user_id' => $userId, 'stage_id' => $submittedStageId],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        }

        foreach ($items as $item) {
            Item::create([
                'description'      => $item['description'],
                'quantity'         => (int) $item['quantity'],
                'unit_cost'        => $item['unit_cost'],
                'total'            => $item['quantity'] * $item['unit_cost'],
                'comments'         => $item['comments'] ?? null,
                'line_item_number' => $item['line_item_number'],
                'requisition_id'   => $requisition->id,
            ]);
        }

        return $requisition->fresh(['items', 'costCenter', 'status']);
    }

    private function syncSuppliers(Requisition $requisition, array $suppliers): void
    {
        $suppliers = RequisitionSupplierQuoteRules::normalizeRecommendedSupplier(
            $suppliers
        );

        $syncData = [];

        foreach ($suppliers as $supplier) {
            $syncData[$supplier['supplier_id']] = [
                'is_recommended'         => $supplier['is_recommended'] ?? false,
                'quoted_total'           => $supplier['quoted_total'] ?? null,
                'quote_reference_number' => $supplier['quote_reference_number'] ?? null,
            ];
        }

        if (!empty($syncData)) {
            $requisition->suppliers()->sync($syncData);
        }
    }

    private function generateRequisitionNumber(): string
    {
        $year = now()->format('Y');
        $count = Requisition::whereYear('created_at', $year)->count() + 1;

        return sprintf('REQ-%s-%04d', $year, $count);
    }

    /**
     * Define visible statuses for each user role.
     */
    private function getVisibleStatusesForRole(string $role): array
    {
        return match (strtolower($role)) {
            'requester' => ['Pending', 'Approved', 'Rejected'],
            'budget-officer', 'vice-president', 'president', 'director-of-finance' => [
                'Pending',
                'Approved',
                'Rejected',
                'Cost Center Review'
            ],
            default => ['Pending', 'Approved', 'Rejected'],
        };
    }

    /**
     * Handle the dashboard metrics request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        // 1. Determine active role context
        $currentRole = $request->get('role') ?? $user->roles()->first()?->role_name ?? 'requester';

        // A user can hold an approver role AND submit their own forms. ?view=submitted
        // lets them explicitly ask for the "my submissions" metrics instead of having
        // it inferred from role, and forces cost-center scoping even for global roles
        // (the global bypass is for the approval queue, not for one's own submissions).
        $isSubmittedView = $request->get('view') === 'submitted';

        $query = Requisition::query();

        // 2. Data Boundary Isolation
        // Global roles (Budget Officer, VP, Finance Director, Purchase Officer) skip this and view all departments
        if ($isSubmittedView || !$this->userHasGlobalRequisitionAccess($user, $currentRole)) {
            $assignedCostCenterIds = $user->costCenters()->pluck('cost_centers.id');

            if ($assignedCostCenterIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'role_context' => $currentRole,
                    'metrics' => $this->getEmptyMetricsResponse($currentRole)
                ]);
            }

            $query->whereIn('requisitions.cost_center_id', $assignedCostCenterIds);
        }

        $now = Carbon::now();

        // 3. Define Stage Mapping based on the active role
        $stageMapping = $this->workflowStageMapping();

        // 4. 🔀 Strategy Split: If it's a management workflow role, run the pipeline aggregation cards
        if (!$isSubmittedView && array_key_exists($currentRole, $stageMapping)) {
            $targetStage = $stageMapping[$currentRole];

            $metricsResult = $query->select(
                // Card 1: Awaiting My Action (Sitting at their specific stage step AND Status is Pending [2])
                DB::raw("SUM(CASE WHEN requisitions.stage_id = {$targetStage} AND requisitions.status_id = 2 THEN 1 ELSE 0 END) as awaiting_my_action"),

                // Card 2: In Pipeline (At or past their step review stage, but not fully Approved [3] or Rejected [4])
                DB::raw("SUM(CASE WHEN requisitions.stage_id >= {$targetStage} AND requisitions.status_id NOT IN (3, 4) THEN 1 ELSE 0 END) as in_pipeline"),

                // Card 3: Approved This Month (Fully Approved [3] system-wide within current month)
                DB::raw("SUM(CASE WHEN requisitions.status_id = 3 AND requisitions.updated_at >= '{$now->startOfMonth()->toDateTimeString()}' THEN 1 ELSE 0 END) as approved_this_month")
            )->first();

            // Card 4: Supplier Requests (suppliers awaiting approve/reject by this workflow role)
            $pendingSupplierRequests = Supplier::pending()->count();

            return response()->json([
                'success' => true,
                'role_context' => $currentRole,
                'metrics' => $this->buildMetricsPayload($metricsResult, true, $pendingSupplierRequests)
            ]);
        } else {
            // --- 📝 BASIC REQUESTER METRICS (Default Fallback) ---
            $metricsResult = $query->select(
                DB::raw("SUM(CASE WHEN requisitions.status_id = 2 THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN requisitions.status_id = 3 THEN 1 ELSE 0 END) as approved"),
                DB::raw("SUM(CASE WHEN requisitions.status_id = 4 THEN 1 ELSE 0 END) as rejected"),
                DB::raw("SUM(CASE WHEN requisitions.status_id = 1 THEN 1 ELSE 0 END) as draft")
            )->first();

            return response()->json([
                'success' => true,
                'role_context' => $currentRole,
                'metrics' => $this->buildMetricsPayload($metricsResult, false)
            ]);
        }
    }

    /**
     * Maps workflow roles to the pipeline stage_id they act on.
     */
    private function workflowStageMapping(): array
    {
        return RequisitionWorkflow::roleStageMapping();
    }

    /**
     * Map database row aggregation to cleanly typecast integers.
     */
    private function buildMetricsPayload($result, bool $isWorkflowLayout, int $pendingSupplierRequests = 0): array
    {
        if ($isWorkflowLayout) {
            return [
                'awaiting_my_action'  => (int) ($result->awaiting_my_action ?? 0),
                'in_pipeline'         => (int) ($result->in_pipeline ?? 0),
                'approved_this_month' => (int) ($result->approved_this_month ?? 0),
                'supplier_requests'   => $pendingSupplierRequests,
            ];
        }

        return [
            'pending'  => (int) ($result->pending ?? 0),
            'approved' => (int) ($result->approved ?? 0),
            'rejected' => (int) ($result->rejected ?? 0),
            'draft'    => (int) ($result->draft ?? 0),
        ];
    }

    /**
     * Return structural fallback zeroes when relations are unassigned.
     */
    private function getEmptyMetricsResponse(string $role): array
    {
        $workflowRoles = ['director-dean', 'budget-officer', 'vice-president', 'director-of-finance', 'purchase-officer'];

        if (in_array($role, $workflowRoles)) {
            return ['awaiting_my_action' => 0, 'in_pipeline' => 0, 'approved_this_month' => 0, 'supplier_requests' => 0];
        }
        return ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'draft' => 0];
    }

    /**
     * Render a decimal hours value (e.g. 0.1) as a human-readable duration (e.g. "6 minutes").
     */
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

    /**
     * Fetch all respective forms scoped by user session role and cost center constraints.
     * Maps to: GET /api/requisitionSystem/requisitions/recent
     */
    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();

        // Resolve what operational layout role this request represents
        $currentRole = $request->get('role') ?? $user->roles()->first()?->role_name ?? 'requester';

        // Workflow roles only care about how long a form sat at their own stage;
        // the requester view cares about totals across the whole pipeline.
        // A user can hold an approver role AND submit their own forms, so
        // ?view=submitted lets them explicitly ask for the "my submissions"
        // view instead of having it inferred (and possibly wrong) from role.
        $targetStageId = $request->get('view') === 'submitted'
            ? null
            : $this->workflowStageMapping()[$currentRole] ?? null;
        $previousStageId = $targetStageId !== null
            ? RequisitionWorkflow::previousStageIdForStage($targetStageId)
            : null;

        // Start a query on your Requisition model with status and stage relations loaded
        $query = \Modules\RequisitionSystem\Models\Requisition::with([
                'status',
                'stage',
                'approvals' => fn ($q) => $q->orderByDesc('signed_at'),
            ])
            ->select([
                'requisitions.*',
                'suppliers.name as dynamic_supplier_name'
            ])
            // Left join pivot table to isolate the recommended supplier assigned to the form
            ->leftJoin('requisition_suppliers', function ($join) {
                $join->on('requisitions.id', '=', 'requisition_suppliers.requisition_id')
                    ->where('requisition_suppliers.is_recommended', true);
            })
            // Left join core suppliers details container table
            ->leftJoin('suppliers', 'requisition_suppliers.supplier_id', '=', 'suppliers.id');

        // Workflow roles should only see forms that have actually reached their stage
        // (or that they've already acted on, e.g. a rejection that bounced back to Draft)
        // -- not everything still sitting at an earlier approver's desk.
        if ($targetStageId !== null) {
            $query->where(function ($q) use ($targetStageId) {
                $q->where('requisitions.stage_id', '>=', $targetStageId)
                    ->orWhereHas('approvals', function ($approvalQuery) use ($targetStageId) {
                        $approvalQuery->where('stage_id', $targetStageId);
                    });
            });
        }

        // If they aren't a global administrative role, isolate records strictly to their assigned Cost Centers.
        // The global-access bypass is for reviewing the approval queue across departments; it shouldn't
        // apply when someone explicitly asked for their own "submitted" view.
        $isSubmittedView = $request->get('view') === 'submitted';

        if ($isSubmittedView || (method_exists($this, 'userHasGlobalRequisitionAccess') && !$this->userHasGlobalRequisitionAccess($user, $currentRole))) {
            $assignedCostCenterIds = $user->costCenters()->pluck('cost_centers.id');
            $query->whereIn('requisitions.cost_center_id', $assignedCostCenterIds);
        }

        // 🚀 REMOVED take() constraint: Retrieve ALL matching forms sorted by newest date
        $allRequisitions = $query->latest('requisitions.date_prepared')
            ->get()
            ->map(function ($requisition) use ($targetStageId, $previousStageId) {
                $base = [
                    'id' => $requisition->id,
                    'number' => $requisition->number,
                    'supplier_name' => $requisition->dynamic_supplier_name ?? 'No Supplier Linked',
                    'date_prepared' => $requisition->date_prepared,
                    'total' => (float) $requisition->total,
                    'current_stage_name' => $requisition->stage?->name ?? $requisition->status?->name ?? 'Director review',
                ];

                if ($targetStageId !== null) {
                    // Workflow role: processing/approval time scoped to their own stage,
                    // not the whole pipeline (arrival = previous stage's approval, or
                    // date_prepared if this is the first approver stage).
                    $arrivalAt = $previousStageId === null
                        ? $requisition->date_prepared
                        : $requisition->approvals
                            ->first(fn ($a) => (int) $a->stage_id === $previousStageId && $a->status === 'approved')
                            ?->signed_at;

                    $ownAction = $requisition->approvals->first(fn ($a) => (int) $a->stage_id === $targetStageId);

                    $processingTimeHours = ($arrivalAt && $ownAction)
                        ? round(($ownAction->signed_at->timestamp - $arrivalAt->timestamp) / 3600, 2)
                        : null;

                    $approvalTimeHours = ($arrivalAt && $ownAction?->status === 'approved')
                        ? round(($ownAction->signed_at->timestamp - $arrivalAt->timestamp) / 3600, 2)
                        : null;

                    $base['processing_time_hours'] = $processingTimeHours;
                    $base['processing_time_display'] = $this->formatDurationHours($processingTimeHours);
                    $base['approval_time_hours'] = $approvalTimeHours;
                    $base['approval_time_display'] = $this->formatDurationHours($approvalTimeHours);

                    return $base;
                }

                // Requester (or unmapped role): totals across the whole pipeline.
                $latestApprovedApproval = $requisition->approvals->first(fn ($a) => $a->status === 'approved');

                $processingTimeHours = in_array($requisition->status_id, [3, 4], true)
                    ? round(($requisition->updated_at->timestamp - $requisition->date_prepared->timestamp) / 3600, 2)
                    : null;

                $approvalTimeHours = $latestApprovedApproval
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
            'data' => $allRequisitions
        ]);
    }
}
