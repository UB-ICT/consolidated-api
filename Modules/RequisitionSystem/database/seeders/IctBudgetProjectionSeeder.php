<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\BudgetYear;
use Modules\RequisitionSystem\Models\CostCenter;
use Modules\RequisitionSystem\Services\BudgetCashFlowImportService;

/**
 * Seeds chart of accounts + ICT Active budget for 2026-2027 from
 * FY26-27 Budget Cash Flow Projection ICT.xlsx (cols A/B/C).
 * Missing descriptions become "Account {no}"; missing amounts become 0.
 */
class IctBudgetProjectionSeeder extends Seeder
{
    private const WORKBOOK = __DIR__.'/data/FY26-27 Budget Cash Flow Projection ICT.xlsx';

    private const COST_CENTER_NAME = 'Information and Communication Technology';

    private const BUDGET_YEAR = '2026-2027';

    public function run(): void
    {
        if (!is_readable(self::WORKBOOK)) {
            throw new \RuntimeException(
                'ICT budget workbook not found at '.self::WORKBOOK
            );
        }

        $importService = app(BudgetCashFlowImportService::class);
        $parsed = $importService->parsePath(self::WORKBOOK, true);

        $costCenter = CostCenter::firstOrCreate(['name' => self::COST_CENTER_NAME]);

        $year = BudgetYear::firstOrCreate(
            ['label' => self::BUDGET_YEAR],
            ['submissions_open' => false]
        );

        $budget = $importService->import(
            $costCenter,
            $year,
            $parsed['accounts'],
            $parsed['line_items'],
            'Active',
            'Seeded from FY26-27 Budget Cash Flow Projection ICT.xlsx',
            true
        );

        $this->command?->info(sprintf(
            'ICT budget #%d seeded for %s (%s): %d accounts, %d line items.',
            $budget->id,
            $costCenter->name,
            $year->label,
            count($parsed['accounts']),
            count($parsed['line_items'])
        ));
    }
}
