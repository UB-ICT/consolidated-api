<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\RequisitionSystem\Models\ConversionRate;
use Modules\RequisitionSystem\Models\Currency;
use Modules\RequisitionSystem\Models\Item;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Models\Status;
use Modules\RequisitionSystem\Http\Requests\RequisitionStoreRequest;
use Illuminate\Support\Facades\Auth;

class RequisitionController extends Controller
{
    /**
     * List all requisitions with their attached suppliers & line items.
     * * Supports: 
     * - Sorting: Descending by default (latest)
     * - Filtering: By priority (?priority=high)
     * - Scoping: By authenticated user's cost centers (?scope=cost_center)
     * * Budget Officer, VP, Director of Finance, and Payroll Officer bypass isolation.
     */
    public function index(Request $request)
    {
        $query = Requisition::with(['suppliers', 'items', 'costCenter', 'stage']);

        // 1. Process Tenant Boundary Scoping
        if ($request->get('scope') === 'cost_center') {
            /** @var \Modules\Auth\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            $globalRoles = ['director-of-finance', 'payroll-officer'];
            $hasGlobalAccess = $user->roles()->whereIn('roles.role_name', $globalRoles)->exists();

            // Standard Users get automatically locked down here
            if (!$hasGlobalAccess) {
                $assignedCostCenterIds = $user->costCenters()->pluck('cost_centers.id');

                if ($assignedCostCenterIds->isEmpty()) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized cost center access.'], 403);
                }

                $query->whereIn('cost_center_id', $assignedCostCenterIds);
            }
        }

        // 2. TARGETED FILTERING (Works for Everyone, including Global Roles)
        // If a Budget Officer passes ?cost_center_id=5, this block will filter the global list down to just that center!
        if ($request->has('cost_center_id')) {
            $query->where('cost_center_id', $request->get('cost_center_id'));
        }

        // 3. Generic filters
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }


        // 🔥 NEW FEATURE FILTERS: Recurring and Scheduling Dashboard Lookups
        if ($request->has('is_recurring')) {
            $query->where('is_recurring', $request->boolean('is_recurring'));
        }

        if ($request->get('filter_alerts') === 'upcoming') {
            // Fetch everything hitting a tracking alert within the next 30 days
            $query->scopeUpcomingReminders(30);
        }

        $requisitions = $query->latest()->get();

        return response()->json([
            'success' => true,
            'count'   => $requisitions->count(),
            'data'    => $requisitions,
        ]);
    }

    /**
     * Create a requisition and its multi-vendor sourcing matrix
     */
    public function store(RequisitionStoreRequest $request)
    {
        $validated = $request->validated();

        $requisition = DB::connection('porsql')->transaction(function () use ($validated, $request) {
            // 1. Create the requisition header including our new field arrays
            $requisition = Requisition::create($request->except('suppliers'));

            // 2. Loop through and format the suppliers payload for the pivot mapping table
            $syncData = [];
            foreach ($request->input('suppliers', []) as $supplier) {
                $syncData[$supplier['supplier_id']] = [
                    'is_recommended'         => $supplier['is_recommended'] ?? false,
                    'quoted_total'           => $supplier['quoted_total'] ?? null,
                    'quote_reference_number' => $supplier['quote_reference_number'] ?? null,
                ];
            }

            if (!empty($syncData)) {
                $requisition->suppliers()->sync($syncData);
            }

            return $requisition;
        });

        return response()->json([
            'success' => true,
            'message' => 'Requisition created successfully.',
            'data' => $requisition->refresh()->load(['suppliers', 'items']),
        ], 201);
    }

    public function show(Requisition $requisition): JsonResponse
    {
        $requisition->load(['items', 'costCenter', 'supplier', 'status']);

        return response()->json([
            'success' => true,
            'data' => $requisition->load(['suppliers', 'items', 'costCenter', 'stage']),
        ]);
    }

    public function update(
        RequisitionStoreRequest $request,
        Requisition $requisition
    ): JsonResponse {
        $requisition = $this->persistRequisition(
            $request->validated(),
            $requisition
        );

        return response()->json([
            'success' => true,
            'message' => 'Requisition updated successfully.',
            'data' => $requisition->refresh()->load(['suppliers', 'items']),
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

    private function persistRequisition(
        array $data,
        ?Requisition $requisition = null
    ): Requisition {
        return DB::connection('porsql')->transaction(function () use ($data, $requisition) {
            $items = $data['items'];
            unset($data['items']);

            $data['total'] = collect($items)->sum(
                fn (array $item) => ($item['quantity'] ?? 0) * ($item['unit_cost'] ?? 0)
            );

            $data['number'] = $data['number']
                ?? $this->generateRequisitionNumber();

            $data['status_id'] = $data['status_id']
                ?? Status::where('name', 'Draft')->value('id')
                ?? Status::query()->value('id');

            $data['currency_id'] = $data['currency_id']
                ?? Currency::query()->value('id');

            $data['conversion_rate_id'] = $data['conversion_rate_id']
                ?? ConversionRate::query()->value('id');

            $data['stage_id'] = $data['stage_id']
                ?? Stage::query()->value('id');

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

            return $requisition->fresh(['items', 'costCenter', 'supplier', 'status']);
        });
    }

    private function generateRequisitionNumber(): string
    {
        $year = now()->format('Y');
        $count = Requisition::whereYear('created_at', $year)->count() + 1;

        return sprintf('REQ-%s-%04d', $year, $count);
    }
}
