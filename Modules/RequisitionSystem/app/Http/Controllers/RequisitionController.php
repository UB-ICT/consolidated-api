<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Http\Requests\RequisitionStoreRequest;
use Illuminate\Http\JsonResponse;
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
     * Get summary metrics for the dashboard cards based on user roles and department scopes.
     * Handles Director/Dean view and Requester view.
     */
    /**
     * Get summary metrics for the dashboard cards based on user roles and department scopes.
     * Handles Director/Dean view and Requester view seamlessly across shared Cost Centers.
     */
    public function dashboardMetrics(): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        // Load both roles and cost centers into memory
        $user->load(['roles', 'costCenters']);

        // Get an array of all assigned cost center IDs using Eloquent
        $userCostCenterIds = $user->costCenters->pluck('id')->toArray();

        // Local development safety fallback if your user_cost_center table row isn't created yet
        if (empty($userCostCenterIds)) {
            $userCostCenterIds = [2];
        }

        // --- CONDITION 1: DIRECTOR OR DEAN DASHBOARD ---
        if ($user->hasRole('director/dean')) {

            // 1. Awaiting My Action (STRICT WORKFLOW SCOPE: Sitting at this specific user's approval stage)
            $awaitingMyAction = Requisition::join('stages', 'requisitions.stage_id', '=', 'stages.id')
                ->join('user_stages', 'user_stages.stage_id', '=', 'stages.id')
                ->where('user_stages.user_id', $user->id)
                ->where('requisitions.status_id', 2) // 2 = Pending
                ->whereIn('requisitions.cost_center_id', $userCostCenterIds)
                ->count();

            // 2. In Pipeline (SHARED COST CENTER SCOPE: All pending forms for this department)
            $inPipeline = Requisition::where('status_id', 2)
                ->whereIn('cost_center_id', $userCostCenterIds)
                ->count();

            // 3. Approved This Month (SHARED COST CENTER SCOPE: Matches requester records)
            $approvedThisMonth = Requisition::where('status_id', 3) // 3 = Approved
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->whereIn('cost_center_id', $userCostCenterIds)
                ->count();

            // 4. Supplier Requests (SHARED COST CENTER SCOPE)
            $supplierRequests = \Modules\RequisitionSystem\Models\Supplier::where('status_id', 2)
                ->whereIn('cost_center_id', $userCostCenterIds)
                ->count();

            return response()->json([
                'success' => true,
                'view_type' => 'director/dean',
                'data' => [
                    'awaiting_action'     => ['count' => $awaitingMyAction, 'status' => 'Ready for your review'],
                    'in_pipeline'         => ['count' => $inPipeline, 'status' => 'All active forms'],
                    'approved_this_month' => ['count' => $approvedThisMonth, 'status' => 'Fully cleared'],
                    'supplier_requests'   => ['count' => $supplierRequests, 'status' => 'Pending Budget Officer']
                ]
            ]);
        }

        // --- CONDITION 2: REQUESTER DASHBOARD ---
        if ($user->hasRole('requester')) {

            // Grab metrics cleanly scoped to the shared cost centers
            $inReviewCount = Requisition::whereIn('cost_center_id', $userCostCenterIds)->where('status_id', 2)->count();
            $approvedCount = Requisition::whereIn('cost_center_id', $userCostCenterIds)->where('status_id', 3)->count();
            $rejectedCount = Requisition::whereIn('cost_center_id', $userCostCenterIds)->where('status_id', 4)->count();

            return response()->json([
                'success' => true,
                'view_type' => 'requester',
                'data' => [
                    'in_review' => ['count' => $inReviewCount, 'status' => 'Awaiting decision'],
                    'approved'  => ['count' => $approvedCount, 'status' => 'Cleared for PO'],
                    'rejected'  => ['count' => $rejectedCount, 'status' => 'Needs follow-up'],
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'view_type' => 'generic',
            'data' => []
        ]);
    }
}
