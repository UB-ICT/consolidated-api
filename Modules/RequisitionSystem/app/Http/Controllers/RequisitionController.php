<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Exports\RequisitionReportExport;
use Modules\RequisitionSystem\Models\Approval;
use Modules\RequisitionSystem\Models\Currency;
use Modules\RequisitionSystem\Models\Item;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Models\Tag;
use Modules\RequisitionSystem\Http\Requests\RequisitionApprovalRequest;
use Modules\RequisitionSystem\Http\Requests\RequisitionPurchaseOrderEmailRequest;
use Modules\RequisitionSystem\Http\Requests\RequisitionPurchaseOrderRequest;
use Modules\RequisitionSystem\Http\Requests\RequisitionPurchaseOrderUploadRequest;
use Modules\RequisitionSystem\Http\Requests\RequisitionStoreRequest;
use Modules\RequisitionSystem\Mail\PurchaseOrderToSupplierMail;
use Modules\RequisitionSystem\Services\RequisitionExportService;
use Modules\RequisitionSystem\Services\RequisitionLogService;
use Modules\RequisitionSystem\Services\RequisitionNotificationService;
use Modules\RequisitionSystem\Support\GuardsRequisitionApproval;
use Modules\RequisitionSystem\Support\GuardsRequisitionCancellation;
use Modules\RequisitionSystem\Support\GuardsRequisitionClosure;
use Modules\RequisitionSystem\Support\GuardsRequisitionEditing;
use Modules\RequisitionSystem\Support\GuardsRequisitionPurchaseOrder;
use Modules\RequisitionSystem\Support\GuardsRequisitionVisibility;
use Modules\RequisitionSystem\Support\RequisitionLinePricing;
use Modules\RequisitionSystem\Support\RequisitionLogAction;
use Modules\RequisitionSystem\Support\RequisitionSupplierQuoteRules;
use Modules\RequisitionSystem\Support\RequisitionWorkflow;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequisitionController extends Controller
{
    use GuardsRequisitionEditing;
    use GuardsRequisitionApproval;
    use GuardsRequisitionCancellation;
    use GuardsRequisitionClosure;
    use GuardsRequisitionPurchaseOrder;
    use GuardsRequisitionVisibility;

    public function __construct(
        private readonly RequisitionLogService $logService,
        private readonly RequisitionNotificationService $notificationService,
        private readonly RequisitionExportService $exportService,
    ) {}

    /**
     * List requisitions visible to the authenticated user (slim payload + pagination).
     *
     * Supports filtering by priority, status, cost center, recurring, and upcoming alerts.
     */
    public function index(Request $request)
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $query = Requisition::with([
            'suppliers',
            'costCenter:id,name,number',
            'stage:id,name',
            'status:id,name',
        ]);

        $this->applyRequisitionVisibilityScope($query, $user);

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

        $perPage = min(100, max(1, $request->integer('per_page', 50)));
        $paginator = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'count'   => $paginator->total(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            'data' => collect($paginator->items())->map(
                fn (Requisition $requisition) => $this->formatRequisitionListItem($requisition)
            )->values(),
        ]);
    }

    /**
     * Roles allowed to export the requisition report. Mirrors
     * ALLOWED_REPORT_ROLES in the frontend's PORReportsPage.
     */
    private const REPORT_ROLES = [
        'budget-officer',
        'director-of-finance',
        'purchase-officer',
        'vice-president',
        'super-admin',
    ];

    /**
     * GET /requisitions/report
     *
     * Registered as a static path ahead of apiResource('requisitions', ...)
     * in routes/api.php so "report" isn't captured as {requisition}.
     */
    public function report(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if (!$user->roles()->whereIn('roles.role_name', self::REPORT_ROLES)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this report.',
            ], 403);
        }

        $filters = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
            'supplier_id' => ['nullable', 'integer'],
            'cost_center_id' => ['nullable', 'integer'],
            'status_id' => ['nullable', 'integer'],
            'number' => ['nullable', 'string'],
            'amount_min' => ['nullable', 'numeric'],
            'amount_max' => ['nullable', 'numeric'],
            'sort_by' => ['nullable', 'string'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
        ]);

        return Excel::download(
            new RequisitionReportExport($filters),
            'requisition-report_' . now()->format('Ymd_His') . '.xlsx'
        );
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
            $this->notificationService->notifyCurrentStageReviewers(
                $requisition->refresh(),
                $user
            );
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

        $this->assertUserCanViewRequisition($requisition->loadMissing('status'), $user);

        return response()->json([
            'success' => true,
            'data' => $this->formatRequisitionResponse(
                $requisition->load(['suppliers.status', 'items.chartOfAccount', 'costCenter', 'stage', 'status', 'attachments.supplier.status', 'tags']),
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

        // Draft autosaves are frequent; only write activity history on submit
        // (approvals / cancel / close / PO actions still log elsewhere).
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
            $this->notificationService->notifyCurrentStageReviewers(
                $requisition->refresh(),
                $user
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

        $this->notificationService->notifyAfterStageAdvance($requisition, $user);

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

    public function uploadPurchaseOrder(
        RequisitionPurchaseOrderUploadRequest $request,
        Requisition $requisition
    ): JsonResponse {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        $this->assertCanManagePurchaseOrderDocument($requisition, $user);

        $file = $request->file('file');
        $storedFileName = Str::uuid() . '.pdf';
        $storedPath = $file->storeAs('uploads/purchase-orders', $storedFileName, 'local');

        if ($requisition->purchase_order_file_path
            && Storage::disk('local')->exists($requisition->purchase_order_file_path)
        ) {
            Storage::disk('local')->delete($requisition->purchase_order_file_path);
        }

        $updates = [
            'purchase_order_file_name' => $file->getClientOriginalName(),
            'purchase_order_file_path' => $storedPath,
            'purchase_order_emailed_at' => null,
        ];

        $purchaseOrderNumber = $request->validated('purchase_order_number');
        if ($purchaseOrderNumber !== null) {
            $updates['purchase_order_number'] = $purchaseOrderNumber !== ''
                ? $purchaseOrderNumber
                : null;
        }

        $requisition->update($updates);

        $this->logService->recordPurchaseOrderDocumentUpload(
            $requisition,
            $user,
            $file->getClientOriginalName()
        );

        $requisition->refresh()->load(['suppliers.status', 'items', 'costCenter', 'stage', 'status']);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order uploaded successfully.',
            'data'    => $this->formatRequisitionResponse($requisition, $user),
        ]);
    }

    public function downloadPurchaseOrder(Requisition $requisition): StreamedResponse|JsonResponse
    {
        if (!$requisition->purchase_order_file_path
            || !Storage::disk('local')->exists($requisition->purchase_order_file_path)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order file not found.',
            ], 404);
        }

        return Storage::disk('local')->response(
            $requisition->purchase_order_file_path,
            $requisition->purchase_order_file_name ?: 'purchase-order.pdf',
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf(
                    'attachment; filename="%s"',
                    $requisition->purchase_order_file_name ?: 'purchase-order.pdf'
                ),
            ]
        );
    }

    public function emailPurchaseOrder(
        RequisitionPurchaseOrderEmailRequest $request,
        Requisition $requisition
    ): JsonResponse {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        $this->assertCanEmailPurchaseOrder($requisition, $user);

        $supplier = $requisition->preferredSupplier();

        Mail::to($supplier->email)->send(new PurchaseOrderToSupplierMail(
            $requisition,
            $supplier,
            $request->validated('message')
        ));

        $requisition->update([
            'purchase_order_emailed_at' => now(),
        ]);

        $this->logService->recordPurchaseOrderEmailed(
            $requisition,
            $user,
            $supplier->email
        );

        $requisition->refresh()->load(['suppliers.status', 'items', 'costCenter', 'stage', 'status']);

        return response()->json([
            'success' => true,
            'message' => sprintf('Purchase order emailed to %s.', $supplier->email),
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

    public function print(Requisition $requisition): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        $this->assertUserCanViewRequisition($requisition->loadMissing('status'), $user);

        try {
            $printPdf = $this->exportService->buildPrintPdf($requisition);
        } catch (\Throwable $exception) {
            Log::error('Requisition print PDF failed.', [
                'requisition_id' => $requisition->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate requisition PDF.',
            ], 500);
        }

        return response()
            ->file($printPdf['path'], [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$printPdf['download_name'].'"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ])
            ->deleteFileAfterSend(true);
    }

    /** @deprecated Use print() — kept as an alias for older clients. */
    public function export(Requisition $requisition): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        return $this->print($requisition);
    }

    private function formatRequisitionListItem(Requisition $requisition): array
    {
        $preferred = $requisition->preferredSupplier()
            ?? $requisition->suppliers->first();

        return [
            'id' => $requisition->id,
            'number' => $requisition->number,
            'requisition_number' => $requisition->number,
            'cost_center_id' => $requisition->cost_center_id,
            'date_prepared' => $requisition->date_prepared,
            'status_id' => $requisition->status_id,
            'stage_id' => $requisition->stage_id,
            'currency_id' => $requisition->currency_id,
            'total' => $requisition->total,
            'priority' => $requisition->priority,
            'current_stage_sequence' => $requisition->current_stage_sequence,
            'cost_center' => $requisition->costCenter
                ? [
                    'id' => $requisition->costCenter->id,
                    'name' => $requisition->costCenter->name,
                    'number' => $requisition->costCenter->number,
                ]
                : null,
            'status' => $requisition->status
                ? [
                    'id' => $requisition->status->id,
                    'name' => $requisition->status->name,
                ]
                : null,
            'stage' => $requisition->stage
                ? [
                    'id' => $requisition->stage->id,
                    'name' => $requisition->stage->name,
                ]
                : null,
            'suppliers' => $preferred
                ? [[
                    'id' => $preferred->id,
                    'name' => $preferred->name,
                    'pivot' => [
                        'is_recommended' => (bool) ($preferred->pivot?->is_recommended ?? false),
                        'quoted_total' => $preferred->pivot?->quoted_total,
                        'quote_reference_number' => $preferred->pivot?->quote_reference_number,
                    ],
                ]]
                : [],
        ];
    }

    private function formatRequisitionResponse(Requisition $requisition, $user): Requisition
    {
        $requisition->loadMissing('status', 'stage', 'tags', 'pipeline.stages', 'suppliers');

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
            'can_upload_purchase_order',
            $this->canManagePurchaseOrderDocument($requisition, $user)
        );

        $requisition->setAttribute(
            'can_email_purchase_order',
            $this->canEmailPurchaseOrder($requisition, $user)
        );

        $requisition->setAttribute(
            'preferred_supplier_email',
            $requisition->preferredSupplier()?->email
        );

        $requisition->setAttribute(
            'requisition_number',
            $requisition->number
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
        $items = $data['items'] ?? [];
        $suppliers = $data['suppliers'] ?? [];
        unset($data['items'], $data['suppliers'], $data['submit'], $data['tag_ids']);

        if (!is_array($items)) {
            $items = [];
        }

        $pricing = RequisitionLinePricing::calculate(
            $items,
            (string) ($data['discount_type'] ?? RequisitionLinePricing::DISCOUNT_NONE),
            (float) ($data['discount_value'] ?? 0)
        );

        $data['discount_type'] = $pricing['discount_type'];
        $data['discount_value'] = $pricing['discount_value'];
        $data['discount_amount'] = $pricing['discount_amount'];
        $data['total'] = $pricing['total'];
        $pricedItems = $pricing['items'];

        $data['quote_waiver_reason'] = RequisitionSupplierQuoteRules::resolveStoredWaiverReason(
            is_array($suppliers) ? $suppliers : [],
            (float) $data['total'],
            $data['quote_waiver_reason'] ?? null
        );

        if ($requisition) {
            // Requisition numbers are immutable after creation.
            unset($data['number']);
        } else {
            $data['number'] = $this->generateRequisitionNumber();
        }

        /** @var User|null $submitter */
        $submitter = Auth::user();
        $skippedDirectorApproval = false;
        $pipelineId = null;

        if (!$requisition) {
            $pipelineId = RequisitionWorkflow::defaultPipelineId();

            if ($shouldSubmit) {
                RequisitionWorkflow::applySubmitState($data, $pipelineId, $submitter);
                $skippedDirectorApproval = RequisitionWorkflow::skipsDirectorApprovalOnSubmit($submitter);
            } else {
                RequisitionWorkflow::applyDraftState($data, $pipelineId);
            }
        } elseif ($shouldSubmit) {
            $pipelineId = RequisitionWorkflow::pipelineIdFor($requisition);

            if ($requisition->status?->name === 'Cost Center Review') {
                RequisitionWorkflow::applyResubmitFromCostCenterReview($data, $requisition);
            } else {
                RequisitionWorkflow::applySubmitState($data, $pipelineId, $submitter);
                $skippedDirectorApproval = RequisitionWorkflow::skipsDirectorApprovalOnSubmit($submitter);
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

        $data['requires_downpayment'] = (bool) ($data['requires_downpayment'] ?? false);

        $description = $data['description'] ?? null;
        $data['description'] = is_string($description) && trim($description) !== ''
            ? trim($description)
            : null;

        if ($requisition) {
            $requisition->update($data);
            $requisition->items()->delete();
        } else {
            $requisition = Requisition::create($data);
        }

        foreach ($pricedItems as $item) {
            $accountId = $item['chart_of_account_id'] ?? null;

            Item::create([
                'chart_of_account_id' => $accountId !== null && $accountId !== ''
                    ? (int) $accountId
                    : null,
                'quantity' => round((float) ($item['quantity'] ?? 0), 4),
                'unit_cost' => $item['unit_cost'] ?? 0,
                'subtotal' => $item['subtotal'] ?? 0,
                'discount_amount' => $item['discount_amount'] ?? 0,
                'gst_applicable' => (bool) ($item['gst_applicable'] ?? false),
                'gst_amount' => $item['gst_amount'] ?? 0,
                'total' => $item['total'] ?? 0,
                'comments' => $item['comments'] ?? null,
                'requisition_id' => $requisition->id,
            ]);
        }

        if ($skippedDirectorApproval && $submitter && $pipelineId) {
            $directorStageId = RequisitionWorkflow::stageIdForSequence(
                RequisitionWorkflow::SUBMITTED_STAGE_SEQUENCE,
                $pipelineId
            );

            if ($directorStageId) {
                Approval::create([
                    'requisition_id' => $requisition->id,
                    'user_id' => $submitter->id,
                    'stage_id' => $directorStageId,
                    'status' => 'approved',
                    'comments' => 'Auto-approved: submitted by director-dean.',
                    'signed_at' => now(),
                ]);
            }
        }

        return $requisition->fresh([
            'items.chartOfAccount',
            'costCenter',
            'status',
            'tags',
        ]);
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

    /**
     * Unique requisition number, zero-padded to at least 3 digits (e.g. 001, 999, 1000, 1001).
     */
    private function generateRequisitionNumber(): string
    {
        // Serialize allocation within the surrounding porsql transaction.
        DB::connection('porsql')->select('SELECT pg_advisory_xact_lock(?)', [872_314_001]);

        $max = DB::connection('porsql')
            ->table('requisitions')
            ->whereRaw("number ~ '^[0-9]+$'")
            ->max(DB::raw('CAST(number AS BIGINT)'));

        $next = ((int) $max) + 1;

        return str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
