<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Budget;
use Modules\RequisitionSystem\Models\BudgetApproval;
use Modules\RequisitionSystem\Models\BudgetYear;
use Modules\RequisitionSystem\Models\CostCenter;

trait GuardsBudgetAccess
{
    /**
     * @return array<int, string>
     */
    protected function budgetFinanceEditorRoles(): array
    {
        return ['budget-officer', 'senior-account', 'director-of-finance'];
    }

    /**
     * @return array<int, string>
     */
    protected function budgetCostCenterRoles(): array
    {
        return ['requester', 'director-dean'];
    }

    protected function userIsBudgetFinanceEditor(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->roles()
            ->whereIn('roles.role_name', $this->budgetFinanceEditorRoles())
            ->exists();
    }

    protected function userIsBudgetCostCenterUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->roles()
            ->whereIn('roles.role_name', $this->budgetCostCenterRoles())
            ->exists();
    }

    protected function userIsBudgetOfficer(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->roles()
            ->where('roles.role_name', 'budget-officer')
            ->exists();
    }

    protected function userAssignedCostCenterIds(User $user)
    {
        return $user->costCenters()->pluck('cost_centers.id');
    }

    protected function userCanViewBudget(Budget $budget, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->userIsBudgetFinanceEditor($user)) {
            return true;
        }

        if (!$this->userIsBudgetCostCenterUser($user)) {
            return false;
        }

        return $this->userAssignedCostCenterIds($user)->contains($budget->cost_center_id);
    }

    protected function userCanEditBudgetLineItems(Budget $budget, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $statusName = $budget->status?->name;

        if (in_array($statusName, BudgetWorkflow::lockedStatuses(), true)) {
            return false;
        }

        if ($this->userIsBudgetFinanceEditor($user)) {
            return !in_array($statusName, BudgetWorkflow::lockedStatuses(), true);
        }

        if (!$this->userIsBudgetCostCenterUser($user)) {
            return false;
        }

        if (!$this->userAssignedCostCenterIds($user)->contains($budget->cost_center_id)) {
            return false;
        }

        return $statusName === 'Draft';
    }

    protected function userCanCreateBudgetForYear(BudgetYear $year, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // Finance editors (budget officer, senior account, DoF) may create
        // budgets outside the open-submissions window for administrative work.
        if ($this->userIsBudgetFinanceEditor($user)) {
            return true;
        }

        // Cost centers may only create once the budget officer opens submissions
        // for that specific year.
        if (!$this->userIsBudgetCostCenterUser($user)) {
            return false;
        }

        return (bool) $year->submissions_open;
    }

    /**
     * Capability flags for the portal budget UI.
     *
     * @return array{
     *   is_finance_editor: bool,
     *   is_cost_center_user: bool,
     *   can_manage_years: bool,
     *   can_create_budget: bool,
     *   assigned_cost_centers: \Illuminate\Support\Collection<int, CostCenter>
     * }
     */
    protected function budgetAccessMeta(?User $user): array
    {
        $isFinance = $this->userIsBudgetFinanceEditor($user);
        $isCostCenter = $this->userIsBudgetCostCenterUser($user);
        $assignedIds = $user
            ? $this->userAssignedCostCenterIds($user)->map(fn ($id) => (int) $id)->values()->all()
            : [];

        $assignedCenters = $assignedIds === []
            ? collect()
            : CostCenter::query()
                ->whereIn('id', $assignedIds)
                ->orderBy('name')
                ->get(['id', 'name']);

        $hasOpenYear = BudgetYear::query()->where('submissions_open', true)->exists();

        $canCreate = false;

        if ($isFinance) {
            $canCreate = true;
        } elseif ($isCostCenter) {
            $canCreate = $hasOpenYear && $assignedIds !== [];
        }

        return [
            'is_finance_editor' => $isFinance,
            'is_cost_center_user' => $isCostCenter && !$isFinance,
            'can_manage_years' => $this->userIsBudgetOfficer($user),
            'can_upload_budget' => $this->userIsBudgetOfficer($user),
            'can_create_budget' => $canCreate,
            'assigned_cost_centers' => $assignedCenters,
        ];
    }

    protected function userCanUploadBudget(?User $user): bool
    {
        return $this->userIsBudgetOfficer($user);
    }

    protected function assertCanUploadBudget(?User $user): void
    {
        $this->assertAuthenticated($user);

        if (!$this->userCanUploadBudget($user)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Only the budget officer can upload budgets from a spreadsheet.',
            ], 403));
        }
    }

    protected function userCanSubmitBudget(Budget $budget, ?User $user): bool
    {
        if (!$this->userCanEditBudgetLineItems($budget, $user)) {
            return false;
        }

        return $budget->status?->name === 'Draft'
            && $this->userIsBudgetCostCenterUser($user);
    }

    protected function userCanActivateBudget(Budget $budget, ?User $user): bool
    {
        if (!$user || !$this->userIsBudgetFinanceEditor($user)) {
            return false;
        }

        return $budget->status?->name === 'Approved';
    }

    protected function userCanDeactivateBudget(Budget $budget, ?User $user): bool
    {
        if (!$user || !$this->userIsBudgetFinanceEditor($user)) {
            return false;
        }

        return $budget->status?->name === 'Active';
    }

    protected function assertCanActivateBudget(Budget $budget, ?User $user): void
    {
        $this->assertAuthenticated($user);
        $budget->loadMissing('status');

        if (!$this->userCanActivateBudget($budget, $user)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Only finance editors can activate an approved budget.',
            ], 403));
        }
    }

    protected function assertCanDeactivateBudget(Budget $budget, ?User $user): void
    {
        $this->assertAuthenticated($user);
        $budget->loadMissing('status');

        if (!$this->userCanDeactivateBudget($budget, $user)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Only finance editors can deactivate an active budget.',
            ], 403));
        }
    }

    protected function userCanOpenBudgetSubmissions(?User $user): bool
    {
        return $this->userIsBudgetOfficer($user);
    }

    /**
     * @return array<int, string>
     */
    protected function budgetDecisionStatuses(): array
    {
        return ['approved', 'rejected', 'cost_center_review'];
    }

    protected function findBudgetStageDecision(Budget $budget, User $user): ?BudgetApproval
    {
        $actingStageId = BudgetWorkflow::matchingUserStageId($budget, $user);

        if (!$actingStageId) {
            return null;
        }

        return BudgetApproval::query()
            ->where('budget_id', $budget->id)
            ->where('user_id', $user->id)
            ->where('stage_id', $actingStageId)
            ->whereIn('status', $this->budgetDecisionStatuses())
            ->latest('id')
            ->first();
    }

    protected function userCanViewBudgetApprovalActions(Budget $budget, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($budget->status?->name !== 'Pending') {
            return false;
        }

        return BudgetWorkflow::matchingUserStageId($budget, $user) !== null;
    }

    protected function canUserApproveBudget(Budget $budget, ?User $user): bool
    {
        if (!$this->userCanViewBudgetApprovalActions($budget, $user)) {
            return false;
        }

        return $this->findBudgetStageDecision($budget, $user) === null;
    }

    protected function assertAuthenticated(?User $user): void
    {
        if (!$user) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401));
        }
    }

    protected function assertCanViewBudget(Budget $budget, ?User $user): void
    {
        $this->assertAuthenticated($user);

        if (!$this->userCanViewBudget($budget, $user)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this budget.',
            ], 403));
        }
    }

    protected function assertCanEditBudgetLineItems(Budget $budget, ?User $user): void
    {
        $this->assertAuthenticated($user);
        $budget->loadMissing('status');

        if (!$this->userCanEditBudgetLineItems($budget, $user)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'You are not authorized to edit this budget.',
            ], 403));
        }
    }

    protected function assertCanApproveBudget(Budget $budget, ?User $user): void
    {
        $this->assertAuthenticated($user);
        $budget->loadMissing('status');

        if (!$this->userCanViewBudgetApprovalActions($budget, $user)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'You are not authorized to act on this budget at its current stage.',
            ], 403));
        }

        if ($this->findBudgetStageDecision($budget, $user)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'You have already recorded a decision for this stage.',
            ], 403));
        }
    }

    protected function assertCanOpenSubmissions(?User $user): void
    {
        $this->assertAuthenticated($user);

        if (!$this->userCanOpenBudgetSubmissions($user)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Only the budget officer can open or close budget submissions.',
            ], 403));
        }
    }
}
