<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Http\Requests\RequisitionStoreRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequisitionController extends Controller
{
    /**
     * List all requisitions with their attached suppliers & line items
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            // 🔥 Eager load suppliers and items to avoid N+1 database queries
            'data' => Requisition::with(['suppliers', 'items'])->latest()->get(),
        ]);
    }

    /**
     * Create a requisition and its multi-vendor sourcing matrix
     */
    public function store(RequisitionStoreRequest $request)
    {
        $validated = $request->validated();

        // Use our database transaction on the 'porsql' connection
        $requisition = DB::connection('porsql')->transaction(function () use ($validated, $request) {
            // 1. Create the requisition header (ignoring the custom suppliers array)
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

            // 3. Attach all vendors and their metadata into the pivot table in one execution block
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
            'data' => $requisition->load(['suppliers', 'items']),
        ]);
    }

    /**
     * Update an existing requisition and its multi-vendor configurations
     */
    public function update(RequisitionStoreRequest $request, Requisition $requisition)
    {
        $validated = $request->validated();

        DB::connection('porsql')->transaction(function () use ($requisition, $request) {
            // 1. Update the main header table properties
            $requisition->update($request->except('suppliers'));

            // 2. Format the updating suppliers data payload matrix
            $syncData = [];
            foreach ($request->input('suppliers', []) as $supplier) {
                $syncData[$supplier['supplier_id']] = [
                    'is_recommended'         => $supplier['is_recommended'] ?? false,
                    'quoted_total'           => $supplier['quoted_total'] ?? null,
                    'quote_reference_number' => $supplier['quote_reference_number'] ?? null,
                ];
            }

            // 3. Synchronize the database. Missing IDs from the payload will be unlinked safely
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
        // Because of cascading deletes on your migration, this auto-wipes entries in requisition_suppliers!
        $requisition->delete();

        return response()->json([
            'success' => true,
            'message' => 'Requisition deleted successfully.',
        ]);
    }

    /**
     * Get all requisitions for the authenticated user's cost center(s) via pivot mapping
     */
    public function byCostCenter()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $assignedCostCenterIds = DB::connection('porsql')
            ->table('user_cost_center')
            ->where('user_id', $user->id)
            ->pluck('cost_center_id');

        if ($assignedCostCenterIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'User is not assigned to any cost center.',
            ], 403);
        }

        // 🔥 Added .with() here too so your cost-center dashboard listings display vendor information immediately
        $requisitions = Requisition::with(['suppliers', 'items'])
            ->whereIn('cost_center_id', $assignedCostCenterIds)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requisitions,
        ]);
    }
}
