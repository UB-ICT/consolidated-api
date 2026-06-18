<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\RequisitionSystem\Models\Currency;
use Modules\RequisitionSystem\Models\Item;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Models\Status;
use Modules\RequisitionSystem\Http\Requests\RequisitionStoreRequest;
use Modules\RequisitionSystem\Services\RequisitionLogService;
use Modules\RequisitionSystem\Support\GuardsRequisitionEditing;
use Modules\RequisitionSystem\Support\RequisitionSupplierQuoteRules;

class RequisitionController extends Controller
{
    use GuardsRequisitionEditing;

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

        return response()->json([
            'success' => true,
            'count'   => $requisitions->count(),
            'data'    => $requisitions,
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

        $requisition = DB::connection('porsql')->transaction(function () use ($validated) {
            $requisition = $this->persistRequisition($validated);
            $this->syncSuppliers($requisition, $validated['suppliers'] ?? []);

            return $requisition;
        });

        $this->logService->recordCreation($requisition, $user);

        return response()->json([
            'success' => true,
            'message' => 'Requisition created successfully.',
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

        $this->assertCostCenterCanEdit($requisition->load('status'), $user);
        $this->assertLineItemsNotAdded($requisition, $validated['items'] ?? [], $user);

        $before = $requisition->replicate();
        $before->setRelation('status', $requisition->status);
        $previousItems = $requisition->items()->get();
        $previousStatusName = $requisition->status?->name;
        $activityComment = $validated['activity_comment'] ?? null;
        unset($validated['activity_comment']);

        $requisition = DB::connection('porsql')->transaction(function () use ($validated, $requisition) {
            $requisition = $this->persistRequisition($validated, $requisition);
            $this->syncSuppliers($requisition, $validated['suppliers'] ?? []);

            return $requisition;
        });

        $requisition->load('status');
        $wasSubmitted = in_array($previousStatusName, $this->costCenterEditableStatuses(), true)
            && $requisition->status?->name === 'Pending'
            && $previousStatusName !== 'Pending';

        if ($wasSubmitted) {
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
            'message' => 'Requisition updated successfully.',
            'data' => $this->formatRequisitionResponse($requisition->refresh(), $user),
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
        $requisition->setAttribute(
            'is_editable',
            $this->isEditableByCostCenter($requisition->loadMissing('status'), $user)
        );

        return $requisition;
    }

    private function persistRequisition(
        array $data,
        ?Requisition $requisition = null
    ): Requisition {
        $items = $data['items'];
        unset($data['items'], $data['suppliers']);

        $data['total'] = collect($items)->sum(
            fn (array $item) => ($item['quantity'] ?? 0) * ($item['unit_cost'] ?? 0)
        );

        $data['number'] = $data['number']
            ?? $this->generateRequisitionNumber();

        if (!$requisition) {
            $data['status_id'] = $data['status_id']
                ?? Status::where('name', 'Draft')->value('id')
                ?? Status::query()->value('id');
        } elseif (!array_key_exists('status_id', $data)) {
            unset($data['status_id']);
        }

        $data['currency_id'] = $data['currency_id']
            ?? Currency::query()->value('id');

        $data['stage_id'] = $data['stage_id']
            ?? Stage::query()->value('id');

        if (!$data['is_recurring']) {
            $data['reminder_date'] = null;
        }

        if ($requisition) {
            $requisition->update($data);
            $requisition->items()->delete();
        } else {
            $requisition = Requisition::create($data);
        }

        foreach ($items as $index => $item) {
            Item::create([
                'description' => $item['description'],
                'quantity' => (int) $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'total' => $item['quantity'] * $item['unit_cost'],
                'comments' => $item['comments'] ?? null,
                'line_item_number' => $index + 1,
                'requisition_id' => $requisition->id,
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
}
