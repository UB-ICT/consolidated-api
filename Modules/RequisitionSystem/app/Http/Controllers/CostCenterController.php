<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Http\Requests\CostCenterStoreRequest;
use Modules\RequisitionSystem\Http\Requests\CostCenterUsersSyncRequest;
use Modules\RequisitionSystem\Models\Budget;
use Modules\RequisitionSystem\Models\CostCenter;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\Tag;
use Modules\RequisitionSystem\Models\UserCostCenter;

class CostCenterController extends Controller
{
    /**
     * Display a listing of cost centers.
     */
    public function index(): JsonResponse
    {
        $costCenters = CostCenter::query()->orderBy('name')->get();
        $usersByCostCenter = $this->usersForCostCenterIds($costCenters->pluck('id')->all());

        $data = $costCenters->map(function (CostCenter $costCenter) use ($usersByCostCenter) {
            return $this->formatCostCenter(
                $costCenter,
                $usersByCostCenter[$costCenter->id] ?? []
            );
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ], 200);
    }

    public function assignedToMe(Request $request): JsonResponse
    {
        $assignment = UserCostCenter::with('costCenter')
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->first();

        $costCenter = $assignment?->costCenter;

        return response()->json([
            'success' => true,
            'data' => $costCenter
                ? $this->formatCostCenter(
                    $costCenter,
                    $this->usersForCostCenterIds([$costCenter->id])[$costCenter->id] ?? []
                )
                : null,
        ]);
    }

    /**
     * Store a newly created cost center in storage.
     */
    public function store(CostCenterStoreRequest $request): JsonResponse
    {
        $costCenter = CostCenter::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cost center created successfully.',
            'data'    => $this->formatCostCenter($costCenter, []),
        ], 201);
    }

    /**
     * Display the specified cost center.
     */
    public function show(CostCenter $costCenter): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->formatCostCenter(
                $costCenter,
                $this->usersForCostCenterIds([$costCenter->id])[$costCenter->id] ?? []
            ),
        ], 200);
    }

    /**
     * Update the specified cost center in storage.
     */
    public function update(CostCenterStoreRequest $request, CostCenter $costCenter): JsonResponse
    {
        $costCenter->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cost center updated successfully.',
            'data'    => $this->formatCostCenter(
                $costCenter->fresh(),
                $this->usersForCostCenterIds([$costCenter->id])[$costCenter->id] ?? []
            ),
        ], 200);
    }

    public function syncUsers(
        CostCenterUsersSyncRequest $request,
        CostCenter $costCenter
    ): JsonResponse {
        $userIds = collect($request->validated('user_ids'))->unique()->values();

        DB::connection('porsql')->transaction(function () use ($costCenter, $userIds) {
            UserCostCenter::query()
                ->where('cost_center_id', $costCenter->id)
                ->whereNotIn('user_id', $userIds)
                ->delete();

            foreach ($userIds as $userId) {
                UserCostCenter::firstOrCreate([
                    'cost_center_id' => $costCenter->id,
                    'user_id' => $userId,
                ]);
            }
        });

        $users = $this->usersForCostCenterIds([$costCenter->id])[$costCenter->id] ?? [];

        return response()->json([
            'success' => true,
            'message' => 'Cost center users updated successfully.',
            'data' => $this->formatCostCenter($costCenter->fresh(), $users),
        ]);
    }

    /**
     * Remove the specified cost center from storage.
     */
    public function destroy(CostCenter $costCenter): JsonResponse
    {
        if (Requisition::query()->where('cost_center_id', $costCenter->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this cost center because it has requisitions.',
            ], 422);
        }

        if (Budget::query()->where('cost_center_id', $costCenter->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this cost center because it has budgets.',
            ], 422);
        }

        if (Tag::query()->where('cost_center_id', $costCenter->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this cost center because it has tags.',
            ], 422);
        }

        if (UserCostCenter::query()->where('cost_center_id', $costCenter->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this cost center because users are assigned to it.',
            ], 422);
        }

        $costCenter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cost center deleted successfully.',
        ], 200);
    }

    /**
     * @param  array<int, array{id: string, name: string, email: string}>  $users
     * @return array{id: int, name: string, number: string|null, users: array<int, array{id: string, name: string, email: string}>}
     */
    private function formatCostCenter(CostCenter $costCenter, array $users): array
    {
        return [
            'id' => $costCenter->id,
            'name' => $costCenter->name,
            'number' => $costCenter->number,
            'users' => $users,
        ];
    }

    /**
     * @param  array<int, int>  $costCenterIds
     * @return array<int, array<int, array{id: string, name: string, email: string}>>
     */
    private function usersForCostCenterIds(array $costCenterIds): array
    {
        if ($costCenterIds === []) {
            return [];
        }

        $assignments = UserCostCenter::query()
            ->whereIn('cost_center_id', $costCenterIds)
            ->get(['cost_center_id', 'user_id']);

        $users = User::query()
            ->whereIn('id', $assignments->pluck('user_id')->unique())
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        $grouped = [];

        foreach ($assignments as $assignment) {
            $user = $users->get($assignment->user_id);

            if (!$user) {
                continue;
            }

            $grouped[$assignment->cost_center_id][] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        }

        return $grouped;
    }
}
