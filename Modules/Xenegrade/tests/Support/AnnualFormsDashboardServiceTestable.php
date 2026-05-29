<?php

namespace Modules\Xenegrade\Tests\Support;

use Modules\Xenegrade\Services\AnnualFormsDashboardService;

/**
 * Exposes protected helpers from AnnualFormsDashboardService for unit testing.
 */
class AnnualFormsDashboardServiceTestable extends AnnualFormsDashboardService
{
    /**
     * @param  list<int>  $counts
     * @return list<int>
     */
    public function exposeScaleNonNegativeIntegersToTargetSum(array $counts, int $targetSum): array
    {
        return $this->scaleNonNegativeIntegersToTargetSum($counts, $targetSum);
    }

    /**
     * @param  array{total: int, notStarted: int, inProgress: int, lecturerSubmitted: int, coordinatorReviewed: int}  $sheetSummary
     * @param  array<string, mixed>|null  $storedCm
     * @return array{total: int, notStarted: int, inProgress: int, lecturerSubmitted: int, coordinatorReviewed: int}
     */
    public function exposeMergeCourseCoordinatorDashboardStatsFromSheetAndReport(array $sheetSummary, ?array $storedCm): array
    {
        return $this->mergeCourseCoordinatorDashboardStatsFromSheetAndReport($sheetSummary, $storedCm);
    }

    public function exposeNormalizeSheetColumnAlias(mixed $label): string
    {
        return $this->normalizeSheetColumnAlias($label);
    }

    public function exposeEmailsEqual(?string $a, ?string $b): bool
    {
        return $this->emailsEqual($a, $b);
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  list<string>  $keys
     */
    public function exposeSheetCell(array $row, array $keys): ?string
    {
        return $this->sheetCell($row, $keys);
    }
}
