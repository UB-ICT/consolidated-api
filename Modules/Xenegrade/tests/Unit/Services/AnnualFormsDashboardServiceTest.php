<?php

namespace Modules\Xenegrade\Tests\Unit\Services;

use Modules\Xenegrade\Services\AnnualFormsDashboardService;
use Modules\Xenegrade\Tests\Support\AnnualFormsDashboardServiceTestable;
use PHPUnit\Framework\TestCase;

class AnnualFormsDashboardServiceTest extends TestCase
{
    private AnnualFormsDashboardService $service;

    private AnnualFormsDashboardServiceTestable $testable;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AnnualFormsDashboardService;
        $this->testable = new AnnualFormsDashboardServiceTestable;
    }

    public function test_resolve_primary_dashboard_role_returns_highest_priority_role(): void
    {
        $this->assertSame('VP', $this->service->resolvePrimaryDashboardRole([
            'lecturer' => true,
            'courseCoordinator' => true,
            'programCoordinator' => true,
            'chair' => true,
            'dean' => true,
            'VP' => true,
        ]));

        $this->assertSame('dean', $this->service->resolvePrimaryDashboardRole([
            'lecturer' => true,
            'dean' => true,
            'chair' => true,
        ]));

        $this->assertSame('lecturer', $this->service->resolvePrimaryDashboardRole([
            'lecturer' => true,
        ]));
    }

    public function test_resolve_primary_dashboard_role_returns_none_when_no_roles_enabled(): void
    {
        $this->assertSame('none', $this->service->resolvePrimaryDashboardRole([]));
        $this->assertSame('none', $this->service->resolvePrimaryDashboardRole([
            'lecturer' => false,
            'dean' => false,
        ]));
    }

    public function test_scale_non_negative_integers_to_target_sum_preserves_total(): void
    {
        $scaled = $this->testable->exposeScaleNonNegativeIntegersToTargetSum([2, 3, 5], 10);

        $this->assertSame([2, 3, 5], $scaled);
        $this->assertSame(10, array_sum($scaled));
    }

    public function test_scale_non_negative_integers_to_target_sum_redistributes_proportionally(): void
    {
        $scaled = $this->testable->exposeScaleNonNegativeIntegersToTargetSum([1, 1, 1], 10);

        $this->assertSame(10, array_sum($scaled));
        $this->assertContains(4, $scaled);
        $this->assertContains(3, $scaled);
    }

    public function test_merge_course_coordinator_dashboard_stats_uses_sheet_when_no_snapshot_exists(): void
    {
        $result = $this->testable->exposeMergeCourseCoordinatorDashboardStatsFromSheetAndReport([
            'total' => 4,
            'notStarted' => 1,
            'inProgress' => 1,
            'lecturerSubmitted' => 1,
            'coordinatorReviewed' => 1,
        ], null);

        $this->assertSame([
            'total' => 4,
            'notStarted' => 1,
            'inProgress' => 1,
            'lecturerSubmitted' => 1,
            'coordinatorReviewed' => 1,
        ], $result);
    }

    public function test_merge_course_coordinator_dashboard_stats_scales_snapshot_to_sheet_total(): void
    {
        $result = $this->testable->exposeMergeCourseCoordinatorDashboardStatsFromSheetAndReport([
            'total' => 10,
            'notStarted' => 0,
            'inProgress' => 0,
            'lecturerSubmitted' => 0,
            'coordinatorReviewed' => 0,
        ], [
            'notStarted' => 1,
            'inProgress' => 1,
            'lecturerSubmitted' => 1,
            'coordinatorReviewed' => 1,
        ]);

        $this->assertSame(10, $result['total']);
        $this->assertSame(10, $result['notStarted'] + $result['inProgress'] + $result['lecturerSubmitted'] + $result['coordinatorReviewed']);
    }

    public function test_normalize_sheet_column_alias_strips_whitespace_and_bom(): void
    {
        $this->assertSame('coursecode', $this->testable->exposeNormalizeSheetColumnAlias(' Course Code '));
        $this->assertSame('coursecode', $this->testable->exposeNormalizeSheetColumnAlias("\xEF\xBB\xBFCourse Code"));
    }

    public function test_sheet_cell_matches_fuzzy_header_names(): void
    {
        $row = [
            'Course Code' => 'CS101',
            'Section' => '01',
        ];

        $this->assertSame('CS101', $this->testable->exposeSheetCell($row, ['courseCode', 'CourseCode']));
        $this->assertSame('01', $this->testable->exposeSheetCell($row, ['courseSection', 'Section']));
    }

    public function test_emails_equal_is_case_insensitive_and_trimmed(): void
    {
        $this->assertTrue($this->testable->exposeEmailsEqual(' Lecturer@UB.edu.bz ', 'lecturer@ub.edu.bz'));
        $this->assertFalse($this->testable->exposeEmailsEqual('', 'lecturer@ub.edu.bz'));
        $this->assertFalse($this->testable->exposeEmailsEqual(null, 'lecturer@ub.edu.bz'));
    }
}
