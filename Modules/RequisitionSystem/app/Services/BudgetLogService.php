<?php

namespace Modules\RequisitionSystem\Services;

use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Budget;
use Modules\RequisitionSystem\Models\BudgetLog;
use Modules\RequisitionSystem\Support\BudgetLogAction;

class BudgetLogService
{
    public function record(
        Budget $budget,
        User $user,
        string $action,
        ?string $summary = null,
        ?string $comments = null
    ): BudgetLog {
        return BudgetLog::create([
            'budget_id' => $budget->id,
            'user_id'   => $user->id,
            'action'    => $action,
            'summary'   => $summary,
            'comments'  => $comments,
        ]);
    }

    public function recordCreated(Budget $budget, User $user): BudgetLog
    {
        return $this->record(
            $budget,
            $user,
            BudgetLogAction::CREATED,
            sprintf(
                'Budget created for %s (%s).',
                $budget->costCenter?->name ?? 'cost center',
                $budget->budgetYear?->label ?? 'year'
            )
        );
    }

    public function recordUpdated(
        Budget $budget,
        User $user,
        ?string $comments = null,
        ?string $summary = null
    ): BudgetLog {
        return $this->record(
            $budget,
            $user,
            BudgetLogAction::UPDATED,
            $summary ?? 'Budget line items updated.',
            $comments
        );
    }

    public function recordSubmission(
        Budget $budget,
        User $user,
        ?string $comments = null
    ): BudgetLog {
        return $this->record(
            $budget,
            $user,
            BudgetLogAction::SUBMITTED,
            sprintf(
                'Budget for %s submitted for review.',
                $budget->budgetYear?->label ?? 'the budget year'
            ),
            $comments
        );
    }

    public function recordApprovalDecision(
        Budget $budget,
        User $user,
        string $action,
        ?string $comments = null,
        ?string $stageName = null
    ): BudgetLog {
        $prefix = $action === BudgetLogAction::APPROVED ? 'Approved' : 'Rejected';
        $summary = $stageName
            ? sprintf('%s at %s stage.', $prefix, $stageName)
            : sprintf('Budget %s.', strtolower($prefix));

        return $this->record($budget, $user, $action, $summary, $comments);
    }

    public function recordCostCenterReviewRequest(
        Budget $budget,
        User $user,
        ?string $comments = null,
        ?string $stageName = null
    ): BudgetLog {
        $summary = $stageName
            ? sprintf('Sent back to cost center for review from %s stage.', $stageName)
            : 'Sent back to cost center for review.';

        return $this->record(
            $budget,
            $user,
            BudgetLogAction::COST_CENTER_REVIEW,
            $summary,
            $comments
        );
    }

    public function recordActivated(
        Budget $budget,
        User $user,
        ?string $comments = null
    ): BudgetLog {
        return $this->record(
            $budget,
            $user,
            BudgetLogAction::ACTIVATED,
            sprintf(
                'Budget for %s activated.',
                $budget->budgetYear?->label ?? 'the budget year'
            ),
            $comments
        );
    }

    public function recordDeactivated(
        Budget $budget,
        User $user,
        ?string $comments = null
    ): BudgetLog {
        return $this->record(
            $budget,
            $user,
            BudgetLogAction::DEACTIVATED,
            sprintf(
                'Budget for %s set to inactive.',
                $budget->budgetYear?->label ?? 'the budget year'
            ),
            $comments
        );
    }

    public function recordComment(
        Budget $budget,
        User $user,
        string $comments
    ): BudgetLog {
        return $this->record(
            $budget,
            $user,
            BudgetLogAction::COMMENT,
            'Comment added.',
            $comments
        );
    }
}
