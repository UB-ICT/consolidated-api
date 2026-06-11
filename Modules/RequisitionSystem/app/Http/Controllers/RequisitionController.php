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

    public function dashboardMetrics(): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $query = Requisition::join('statuses', 'requisitions.status_id', '=', 'statuses.id');

        // Apply role-based scoping to matching cost centers
        if ($user && $user->hasRole('cost-center') && $user->department) {
            $userCostCenterIds = $user->department->costCenters()->pluck('id')->toArray();
            $query->whereIn('requisitions.cost_center_id', $userCostCenterIds);
        }

        $metrics = $query->select('statuses.name', DB::raw('count(*) as total_count'))
            ->groupBy('statuses.name')
            ->get()
            ->keyBy('name');

        return response()->json([
            'success' => true,
            'data' => [
                'in_review' => [
                    'count'  => $metrics->get('Pending')->total_count ?? 0,
                    'status' => 'Awaiting decision'
                ],
                'approved' => [
                    'count'  => $metrics->get('Approved')->total_count ?? 0,
                    'status' => 'Cleared for PO'
                ],
                'rejected' => [
                    'count'  => $metrics->get('Rejected')->total_count ?? 0,
                    'status' => 'Needs follow-up'
                ],
            ]
        ]);
    }
}
