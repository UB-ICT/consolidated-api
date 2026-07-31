<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\RequisitionSystem\Http\Requests\BudgetYearStoreRequest;
use Modules\RequisitionSystem\Models\BudgetYear;
use Modules\RequisitionSystem\Support\GuardsBudgetAccess;

class BudgetYearController extends Controller
{
    use GuardsBudgetAccess;

    public function index(): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertAuthenticated($user);

        $years = BudgetYear::query()
            ->orderByDesc('label')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $years,
            'meta'    => $this->budgetAccessMeta($user),
        ]);
    }

    public function store(BudgetYearStoreRequest $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanOpenSubmissions($user);

        $year = BudgetYear::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Budget year created successfully.',
            'data'    => $year,
        ], 201);
    }

    public function show(BudgetYear $budgetYear): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $budgetYear,
        ]);
    }

    public function update(BudgetYearStoreRequest $request, BudgetYear $budgetYear): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanOpenSubmissions($user);

        $budgetYear->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Budget year updated successfully.',
            'data'    => $budgetYear,
        ]);
    }

    public function destroy(BudgetYear $budgetYear): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanOpenSubmissions($user);

        if ($budgetYear->budgets()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a budget year that already has budgets.',
            ], 422);
        }

        $budgetYear->delete();

        return response()->json([
            'success' => true,
            'message' => 'Budget year deleted successfully.',
        ]);
    }

    public function openSubmissions(BudgetYear $budgetYear): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanOpenSubmissions($user);

        $budgetYear->update(['submissions_open' => true]);

        return response()->json([
            'success' => true,
            'message' => sprintf('Budget submissions opened for %s.', $budgetYear->label),
            'data'    => $budgetYear->fresh(),
        ]);
    }

    public function closeSubmissions(BudgetYear $budgetYear): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = Auth::user();
        $this->assertCanOpenSubmissions($user);

        $budgetYear->update(['submissions_open' => false]);

        return response()->json([
            'success' => true,
            'message' => sprintf('Budget submissions closed for %s.', $budgetYear->label),
            'data'    => $budgetYear->fresh(),
        ]);
    }
}
