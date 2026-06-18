<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Http\Requests\RequisitionStoreRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    /**
     * Show an individual requisition with its attached sourcing vendors
     */
    public function show(Requisition $requisition)
    {
        return response()->json([
            'success' => true,
            'data' => $requisition->load(['suppliers', 'items', 'costCenter', 'stage']),
        ]);
    }

    /**
     * Update an existing requisition and its multi-vendor configurations
     */
    public function update(RequisitionStoreRequest $request, Requisition $requisition)
    {
        $validated = $request->validated();

        DB::connection('porsql')->transaction(function () use ($requisition, $request) {
            $requisition->update($request->except('suppliers'));

            $syncData = [];
            foreach ($request->input('suppliers', []) as $supplier) {
                $syncData[$supplier['supplier_id']] = [
                    'is_recommended'         => $supplier['is_recommended'] ?? false,
                    'quoted_total'           => $supplier['quoted_total'] ?? null,
                    'quote_reference_number' => $supplier['quote_reference_number'] ?? null,
                ];
            }

            $requisition->suppliers()->sync($syncData);
        });

        return response()->json([
            'success' => true,
            'message' => 'Requisition updated successfully.',
            'data' => $requisition->refresh()->load(['suppliers', 'items']),
        ]);
    }

    /**
     * Delete requisition
     */
    public function destroy(Requisition $requisition)
    {
        $requisition->delete();

        return response()->json([
            'success' => true,
            'message' => 'Requisition deleted successfully.',
        ]);
    }
}
