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
     * List all requisitions
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Requisition::all(),
        ]);
    }

    /**
     * Create requisition
     */
    public function store(RequisitionStoreRequest $request)
    {
        $requisition = Requisition::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Requisition created successfully.',
            'data' => $requisition,
        ], 201);
    }

    /**
     * Show requisition
     */
    public function show(Requisition $requisition)
    {
        return response()->json([
            'success' => true,
            'data' => $requisition,
        ]);
    }

    /**
     * Update requisition
     */
    public function update(RequisitionStoreRequest $request, Requisition $requisition)
    {
        $requisition->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Requisition updated successfully.',
            'data' => $requisition,
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

        // Pull all cost center IDs assigned to this specific user from your custom pivot table
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

        // Query requisitions belonging to any of those retrieved cost center ids
        $requisitions = Requisition::whereIn('cost_center_id', $assignedCostCenterIds)->get();

        return response()->json([
            'success' => true,
            'data' => $requisitions,
        ]);
    }
}
