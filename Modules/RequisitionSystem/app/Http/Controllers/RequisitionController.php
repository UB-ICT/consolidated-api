<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\RequisitionSystem\Models\Approval;
use Modules\RequisitionSystem\Models\Currency;
use Modules\RequisitionSystem\Models\Item;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Models\Tag;
use Modules\RequisitionSystem\Http\Requests\RequisitionApprovalRequest;
use Modules\RequisitionSystem\Http\Requests\RequisitionPurchaseOrderRequest;
use Modules\RequisitionSystem\Http\Requests\RequisitionStoreRequest;
use Modules\RequisitionSystem\Services\RequisitionLogService;
use Modules\RequisitionSystem\Support\GuardsRequisitionApproval;
use Modules\RequisitionSystem\Support\GuardsRequisitionCancellation;
use Modules\RequisitionSystem\Support\GuardsRequisitionClosure;
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
    use GuardsRequisitionClosure;
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
        $query = Requisition::with(['suppliers.status', 'items', 'costCenter', 'stage', 'status', 'attachments.supplier.status', 'tags']);

        if ($request->get('scope') === 'cost_center') {
            /** @var \Modules\Auth\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            if (!$this->userHasGlobalRequisitionAccess($user)) {
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
                fn (Requisition $requisition) => $this->formatRequisitionResponse($requisition, $user)
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

        $requisition = DB::connection('porsql')->transaction(function () use ($validated, $shouldSubmit) {
            $tagIds = $validated['tag_ids'] ?? null;
            unset($validated['tag_ids']);

            $requisition = $this->persistRequisition($validated, null, $shouldSubmit);
            $this->syncSuppliers($requisition, $validated['suppliers'] ?? []);
            $this->syncTags($requisition, $tagIds);

            return $requisition;
        });

        if ($shouldSubmit) {
            $this->logService->recordSubmission($requisition, $user);
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
                $requisition->load(['suppliers.status', 'items', 'costCenter', 'stage', 'status', 'attachments.supplier.status', 'tags']),
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
            $tagIds = array_key_exists('tag_ids', $validated) ? $validated['tag_ids'] : null;
            unset($validated['tag_ids']);

            $requisition = $this->persistRequisition($validated, $requisition, $shouldSubmit);
            $this->syncSuppliers($requisition, $validated['suppliers'] ?? []);
            $this->syncTags($requisition, $tagIds);

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

    public function close(
        RequisitionApprovalRequest $request,
        Requisition $requisition
    ): JsonResponse {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        $this->assertUserCanClose($requisition, $user);

        $comments = $request->validated('comments');

        RequisitionWorkflow::applyClosure($requisition->refresh());

        $requisition->refresh()->load(['suppliers.status', 'items', 'costCenter', 'stage', 'status', 'tags']);

        $this->logService->recordClosure($requisition, $user, $comments);

        return response()->json([
            'success' => true,
            'message' => 'Requisition closed successfully.',
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

    private function formatRequisitionResponse(Requisition $requisition, $user): Requisition
    {
        $requisition->loadMissing('status', 'stage', 'tags', 'pipeline.stages');

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

        $requisition->setAttribute(
            'can_close',
            $this->userCanCloseRequisition($requisition, $user)
        );

        return $requisition;
    }

    private function persistRequisition(
        array $data,
        ?Requisition $requisition = null,
        bool $shouldSubmit = false
    ): Requisition {
        $items = $data['items'];
        unset($data['items'], $data['suppliers'], $data['submit'], $data['tag_ids']);

        $data['total'] = collect($items)->sum(
            fn (array $item) => ($item['quantity'] ?? 0) * ($item['unit_cost'] ?? 0)
        );

        $data['number'] = $data['number']
            ?? $this->generateRequisitionNumber();

        if (!$requisition) {
            $pipelineId = RequisitionWorkflow::defaultPipelineId();

            if ($shouldSubmit) {
                RequisitionWorkflow::applySubmitState($data, $pipelineId);
            } else {
                RequisitionWorkflow::applyDraftState($data, $pipelineId);
            }
        } elseif ($shouldSubmit) {
            $pipelineId = RequisitionWorkflow::pipelineIdFor($requisition);

            if ($requisition->status?->name === 'Cost Center Review') {
                RequisitionWorkflow::applyResubmitFromCostCenterReview($data, $requisition);
            } else {
                RequisitionWorkflow::applySubmitState($data, $pipelineId);
            }
        } else {
            unset(
                $data['status_id'],
                $data['stage_id'],
                $data['current_stage_sequence'],
                $data['pipeline_id']
            );
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

        return $requisition->fresh(['items', 'costCenter', 'status', 'tags']);
    }

    /**
     * @param  array<int, int>|null  $tagIds
     */
    private function syncTags(Requisition $requisition, ?array $tagIds): void
    {
        if ($tagIds === null) {
            return;
        }

        $validIds = Tag::query()
            ->where('cost_center_id', $requisition->cost_center_id)
            ->whereIn('id', $tagIds)
            ->pluck('id')
            ->all();

        $requisition->tags()->sync($validIds);
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
}
