<?php

namespace Modules\RequisitionSystem\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;

trait GuardsRequisitionVisibility
{
    protected function applyRequisitionVisibilityScope(Builder $query, User $user): void
    {
        RequisitionVisibility::constrainQuery($query, $user);
    }

    protected function userCanViewRequisition(Requisition $requisition, ?User $user): bool
    {
        return RequisitionVisibility::userCanView($requisition, $user);
    }

    protected function assertUserCanViewRequisition(Requisition $requisition, ?User $user): void
    {
        if (!$user) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401));
        }

        if ($this->userCanViewRequisition($requisition, $user)) {
            return;
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'You are not authorized to view this requisition.',
        ], 403));
    }
}
