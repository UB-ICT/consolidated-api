<?php

namespace Modules\RequisitionSystem\Support;

trait GuardsRequisitionReporting
{
    /**
     * Roles permitted to view/export the requisition report. Deliberately
     * separate from GuardsRequisitionEditing::globalRequisitionRoles() —
     * that list also includes payroll-officer, who should not have access
     * to this report.
     */
    protected function reportViewerRoles(): array
    {
        return [
            'budget-officer',
            'director-of-finance',
            'purchase-officer',
            'vice-president',
            'super-admin',
        ];
    }
}
