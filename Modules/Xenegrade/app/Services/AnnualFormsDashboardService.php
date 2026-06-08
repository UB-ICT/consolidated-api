<?php

namespace Modules\Xenegrade\Services;

use App\Services\FirestoreService;
use App\Services\GoogleSheetService;
use Illuminate\Support\Facades\Log;

class AnnualFormsDashboardService
{
    protected string $courseMonitoringCollection = 'cmon_courseMonitoring';

    protected string $programReportsCollection = 'cmon_programCoordinatorReport';

    protected string $chairApvrCollection = 'cmon_ChairAnnualProgramValidationReport';

    protected string $courseCoordinatorReportCollection = 'cmon_courseCoordinatorReport';

    /**
     * @param  array<string, bool>  $roles
     * @return array<string, mixed>
     */
    public function buildDashboardPayload(string $email, string $academicYear, string $semester, array $roles): array
    {
        $spreadsheetId = env('GOOGLE_SHEET_ID');
        $coursesRange = env('COURSES_GOOGLE_SHEET_RANGE', 'courses!A1:Z2000');

        $primary = $this->resolvePrimaryDashboardRole($roles);

        $payload = [
            'email' => $email,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'roles' => $roles,
            'primaryDashboardRole' => $primary,
            'organizationCourseMonitoring' => null,
            'organizationProgramCoordinatorForms' => null,
            'byRole' => [],
        ];

        $courseRows = [];
        if ($spreadsheetId) {
            $courseRows = $this->getDashboardCourseRows($spreadsheetId, $coursesRange, $semester, $academicYear);
            $payload['organizationCourseMonitoring'] = $this->aggregateCourseMonitoringForSheet($courseRows, $academicYear, $semester);
            $orgPcForms = $this->aggregateOrganizationProgramCoordinatorForms($academicYear, null);
            $payload['organizationProgramCoordinatorForms'] = $orgPcForms;
            if (! empty($roles['chair'])) {
                $payload['organizationProgramCoordinatorFormsAsChair'] = $this->aggregateOrganizationProgramCoordinatorForms(
                    $academicYear,
                    fn (array $assoc): bool => $this->emailsEqual($this->sheetCell($assoc, ['Chair']), $email)
                        || $this->emailsEqual($this->sheetCell($assoc, ['Chair 2', 'Chair2', 'chair2']), $email)
                );
            }
            if (! empty($roles['dean'])) {
                $payload['organizationProgramCoordinatorFormsAsDean'] = $this->aggregateOrganizationProgramCoordinatorForms(
                    $academicYear,
                    fn (array $assoc): bool => $this->emailsEqual($this->sheetCell($assoc, ['Dean', 'dean']), $email)
                );
            }
            if (! empty($roles['VP'])) {
                $payload['organizationProgramCoordinatorFormsAsVp'] = $this->aggregateOrganizationProgramCoordinatorForms(
                    $academicYear,
                    fn (array $assoc): bool => $this->emailsEqual(
                        $this->sheetCell($assoc, ['VP', 'vp', 'VicePresident', 'Vice President', 'vice president']),
                        $email
                    )
                );
            }
            $orgChair = $this->aggregateOrganizationChairProgramReportsAndApvr($academicYear);
            $payload['organizationChairProgramMonitoringReview'] = $orgChair['chairProgramMonitoringReview'];
            $payload['organizationChairAnnualProgramValidation'] = $orgChair['chairAnnualProgramValidation'];
            $payload['meta'] = [
                'courseSheetRowCount' => count($courseRows),
                'courseSheetSemesterFilter' => $semester,
                'courseSheetAcademicYearFilter' => $academicYear,
                'programCoordinatorFormsTotal' => $orgPcForms['total'],
            ];
        } else {
            $payload['meta'] = [
                'courseSheetRowCount' => 0,
                'courseSheetSemesterFilter' => $semester,
                'courseSheetAcademicYearFilter' => $academicYear,
                'programCoordinatorFormsTotal' => 0,
                'warning' => 'GOOGLE_SHEET_ID is not configured; course monitoring counts are empty.',
            ];
        }

        if (! empty($roles['lecturer'])) {
            $payload['byRole']['lecturer'] = [
                'courseMonitoring' => $spreadsheetId
                    ? $this->courseMonitoringStatsForLecturer($courseRows, $academicYear, $semester, $email)
                    : $this->emptyCourseMonitoringStats(),
            ];
        }

        if (! empty($roles['courseCoordinator'])) {
            $payload['byRole']['courseCoordinator'] = [
                'courseMonitoring' => $spreadsheetId
                    ? $this->courseMonitoringStatsForCourseCoordinator($courseRows, $academicYear, $semester, $email)
                    : $this->emptyCourseMonitoringStats(),
            ];
        }

        if (! empty($roles['programCoordinator'])) {
            // Program coordinator dashboard stats: program-coordinators sheet + programCoordinatorReports only
            // (not the courses sheet; course monitoring stays on the courses tab for other roles).
            $payload['byRole']['programCoordinator'] = [
                'programAnnualMonitoringReport' => $this->programCoordinatorReportStats($email, $academicYear),
            ];
        }

        if (! empty($roles['chair'])) {
            $payload['byRole']['chair'] = [
                'courseMonitoring' => $spreadsheetId
                    ? $this->courseMonitoringStatsForChair($courseRows, $academicYear, $semester, $email)
                    : $this->emptyCourseMonitoringStats(),
                'chairProgramMonitoringReview' => $this->chairPcProgramReviewStats($email, $academicYear),
                'chairAnnualProgramValidation' => $this->chairApvrStats($email, $academicYear),
            ];
        }

        if (! empty($roles['dean'])) {
            $payload['byRole']['dean'] = [
                'courseMonitoring' => $spreadsheetId
                    ? $this->courseMonitoringStatsForDeanOrVp($courseRows, $academicYear, $semester, $email, 'dean')
                    : $this->emptyCourseMonitoringStats(),
            ];
        }

        if (! empty($roles['VP'])) {
            $payload['byRole']['VP'] = [
                'courseMonitoring' => $spreadsheetId
                    ? $this->courseMonitoringStatsForDeanOrVp($courseRows, $academicYear, $semester, $email, 'VP')
                    : $this->emptyCourseMonitoringStats(),
            ];
        }

        return $payload;
    }

    /**
     * @param  array<string, bool>  $roles
     */
    public function resolvePrimaryDashboardRole(array $roles): string
    {
        if (! empty($roles['VP'])) {
            return 'VP';
        }
        if (! empty($roles['dean'])) {
            return 'dean';
        }
        if (! empty($roles['chair'])) {
            return 'chair';
        }
        if (! empty($roles['programCoordinator'])) {
            return 'programCoordinator';
        }
        if (! empty($roles['courseCoordinator'])) {
            return 'courseCoordinator';
        }
        if (! empty($roles['lecturer'])) {
            return 'lecturer';
        }

        return 'none';
    }

    /**
     * @return array{total: int, notStarted: int, inProgress: int, lecturerSubmitted: int, coordinatorReviewed: int}
     */
    protected function emptyCourseMonitoringStats(): array
    {
        return [
            'total' => 0,
            'notStarted' => 0,
            'inProgress' => 0,
            'lecturerSubmitted' => 0,
            'coordinatorReviewed' => 0,
        ];
    }

    /**
     * Loads all course-sheet rows, then filters by academic year / semester with fallbacks so dashboard
     * counts still work when settings semester text does not exactly match the sheet or the sheet omits columns.
     *
     * @return list<array<string, string|null>>
     */
    protected function getDashboardCourseRows(string $spreadsheetId, string $range, string $semester, string $academicYear): array
    {
        $all = GoogleSheetService::getRowsAssocForSemester($spreadsheetId, $range, null);
        if ($all === []) {
            return [];
        }

        $matchesSemester = function (array $row) use ($semester): bool {
            if (trim($semester) === '') {
                return true;
            }
            $rowSem = $this->sheetCell($row, ['Semester', 'semester', 'Term', 'term', 'Sem', 'SEM']);
            if ($rowSem === null) {
                return false;
            }

            return strcasecmp(trim($rowSem), trim($semester)) === 0;
        };

        $matchesAcademicYear = function (array $row) use ($academicYear): bool {
            if (trim($academicYear) === '') {
                return true;
            }
            $ay = $this->sheetCell($row, [
                'AcademicYear',
                'academicYear',
                'Academic Year',
                'AcademicYr',
                'SchoolYear',
                'schoolYear',
            ]);
            if ($ay === null) {
                return true;
            }

            return strcasecmp(trim($ay), trim($academicYear)) === 0;
        };

        $filtered = [];
        foreach ($all as $row) {
            if ($matchesAcademicYear($row) && $matchesSemester($row)) {
                $filtered[] = $row;
            }
        }
        if ($filtered !== []) {
            return $filtered;
        }

        foreach ($all as $row) {
            if ($matchesAcademicYear($row)) {
                $filtered[] = $row;
            }
        }
        if ($filtered !== []) {
            return $filtered;
        }

        if (trim($semester) !== '') {
            foreach ($all as $row) {
                if ($matchesSemester($row)) {
                    $filtered[] = $row;
                }
            }
        }
        if ($filtered !== []) {
            return $filtered;
        }

        return $all;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    protected function academicYearFromCourseRow(array $row, string $fallback): string
    {
        $ay = $this->sheetCell($row, [
            'AcademicYear',
            'academicYear',
            'Academic Year',
            'AcademicYr',
            'SchoolYear',
            'schoolYear',
        ]);

        return $ay !== null ? trim($ay) : trim($fallback);
    }

    /**
     * @param  array<string, string|null>  $row
     */
    protected function semesterFromCourseRow(array $row, string $fallback): string
    {
        $sem = $this->sheetCell($row, ['Semester', 'semester', 'Term', 'term', 'Sem', 'SEM']);
        if ($sem === null) {
            return trim($fallback);
        }

        return trim($sem);
    }

    /**
     * Maps one courses-sheet row to one dashboard section. Course code + section are required; lecturer email
     * is optional unless {@see $requireLecturerEmail} (used for lecturer-only views).
     *
     * @return array{instructorEmail: string, courseCode: string, section: string, academicYear: string, semester: string}|null
     */
    protected function courseSheetSectionAssignment(
        array $row,
        string $defaultAcademicYear,
        string $defaultSemester,
        bool $requireLecturerEmail
    ): ?array {
        $courseCode = $this->sheetCell($row, [
            'CourseCode', 'courseCode', 'Course Code', 'course code', 'CourseID', 'courseId', 'Course', 'course',
        ]);
        $section = $this->sheetCell($row, [
            'CourseSection', 'courseSection', 'Course Section', 'course section', 'SectionID', 'sectionId',
            'Section', 'section', 'Sec', 'sec',
        ]);
        if ($courseCode === null || $section === null) {
            return null;
        }

        $instRaw = $this->sheetCell($row, [
            'InstructorEmail', 'instructorEmail', 'Instructor Email', 'instructor email',
            'Email', 'email', 'LecturerEmail', 'lecturerEmail', 'Lecturer Email', 'FacultyEmail', 'facultyEmail',
        ]);
        $inst = $instRaw !== null ? trim($instRaw) : '';
        if ($requireLecturerEmail && $inst === '') {
            return null;
        }

        return [
            'instructorEmail' => $inst,
            'courseCode' => trim($courseCode),
            'section' => trim($section),
            'academicYear' => $this->academicYearFromCourseRow($row, $defaultAcademicYear),
            'semester' => $this->semesterFromCourseRow($row, $defaultSemester),
        ];
    }

    /**
     * @param  array<string, string|null>  $row
     * @return array{instructorEmail: string, courseCode: string, section: string, academicYear: string, semester: string}|null
     */
    protected function baseAssignmentFromRow(array $row, string $defaultAcademicYear, string $defaultSemester): ?array
    {
        return $this->courseSheetSectionAssignment($row, $defaultAcademicYear, $defaultSemester, true);
    }

    /**
     * Course coordinator scope on the **courses** sheet (rows where this user is the course coordinator).
     *
     * @param  list<array<string, string|null>>  $rows
     * @return list<array{instructorEmail: string, courseCode: string, section: string, academicYear: string, semester: string}>
     */
    protected function collectCourseCoordinatorAssignments(
        array $rows,
        string $viewerEmail,
        string $defaultAcademicYear,
        string $defaultSemester
    ): array {
        $out = [];

        foreach ($rows as $row) {
            $cc = $this->sheetCell($row, [
                'CourseCoordinator', 'courseCoordinator', 'Course Coordinator', 'course coordinator',
            ]);

            $assignment = $this->courseSheetSectionAssignment($row, $defaultAcademicYear, $defaultSemester, false);
            if ($assignment === null) {
                continue;
            }

            if ($this->emailsEqual($cc, $viewerEmail)) {
                $out[] = $assignment;
            }
        }

        return $out;
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @return list<array{instructorEmail: string, courseCode: string, section: string, academicYear: string, semester: string}>
     */
    protected function collectChairAssignments(
        array $rows,
        string $chairEmail,
        string $defaultAcademicYear,
        string $defaultSemester
    ): array {
        $out = [];

        foreach ($rows as $row) {
            $chair1 = $this->sheetCell($row, ['Chair']);
            $chair2 = $this->sheetCell($row, ['Chair 2', 'Chair2', 'chair2']);

            $assignment = $this->courseSheetSectionAssignment($row, $defaultAcademicYear, $defaultSemester, false);
            if ($assignment === null) {
                continue;
            }

            if ($this->emailsEqual($chair1, $chairEmail) || $this->emailsEqual($chair2, $chairEmail)) {
                $out[] = $assignment;
            }
        }

        return $out;
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @return list<array{instructorEmail: string, courseCode: string, section: string, academicYear: string, semester: string}>
     */
    protected function collectDeanVpAssignments(
        array $rows,
        string $viewerEmail,
        string $kind,
        string $defaultAcademicYear,
        string $defaultSemester
    ): array {
        $out = [];

        foreach ($rows as $row) {
            $assignment = $this->courseSheetSectionAssignment($row, $defaultAcademicYear, $defaultSemester, false);
            if ($assignment === null) {
                continue;
            }

            $ok = $kind === 'dean'
                ? $this->emailsEqual($this->sheetCell($row, ['Dean', 'dean']), $viewerEmail)
                : $this->emailsEqual($this->sheetCell($row, ['VP', 'vp', 'VicePresident', 'Vice President', 'vice president']), $viewerEmail);

            if ($ok) {
                $out[] = $assignment;
            }
        }

        return $out;
    }

    /**
     * @param  list<array{instructorEmail: string, courseCode: string, section: string, academicYear: string, semester: string}>  $assignments
     * @return array{total: int, notStarted: int, inProgress: int, lecturerSubmitted: int, coordinatorReviewed: int}
     */
    protected function summarizeAssignments(array $assignments): array
    {
        $stats = $this->emptyCourseMonitoringStats();
        $cache = [];

        foreach ($assignments as $a) {
            $stats['total']++;
            $lecturerEmail = trim($a['instructorEmail'] ?? '');
            if ($lecturerEmail === '') {
                $stats['notStarted']++;

                continue;
            }

            $bucket = $this->classifySection(
                $lecturerEmail,
                $a['courseCode'],
                $a['section'],
                $a['academicYear'],
                $a['semester'],
                $cache
            );
            $stats[$bucket]++;
        }

        return $stats;
    }

    /**
     * @param  array<string, array<string, mixed>|null>  $cache
     */
    protected function classifySection(
        string $instructorEmail,
        string $courseCode,
        string $section,
        string $academicYear,
        string $semester,
        array &$cache
    ): string {
        if (trim($instructorEmail) === '') {
            return 'notStarted';
        }

        $key = strtolower(trim($instructorEmail));
        if (! isset($cache[$key])) {
            try {
                $cache[$key] = FirestoreService::getDocument($this->courseMonitoringCollection, $instructorEmail);
            } catch (\Throwable $e) {
                Log::warning('Dashboard Firestore read failed', ['email' => $instructorEmail, 'error' => $e->getMessage()]);
                $cache[$key] = null;
            }
        }

        $doc = $cache[$key];
        if ($doc === null) {
            return 'notStarted';
        }

        $courses = $doc['courses'] ?? [];
        if (! is_array($courses)) {
            return 'notStarted';
        }

        $course = $this->findCourseInDoc($courses, $courseCode, $section, $academicYear, $semester);
        if ($course === null) {
            return 'notStarted';
        }

        $submitted = ! empty($course['hasLecturerSubmitted']);
        $reviewed = ! empty($course['hasCoordinatorApproved']);

        if ($reviewed) {
            return 'coordinatorReviewed';
        }
        if ($submitted) {
            return 'lecturerSubmitted';
        }

        return 'inProgress';
    }

    /**
     * @param  list<array<string, mixed>>  $courses
     * @return array<string, mixed>|null
     */
    protected function findCourseInDoc(array $courses, string $courseId, string $courseSection, string $academicYear, string $semester): ?array
    {
        foreach ($courses as $course) {
            $courseDetails = $course['courseDetails'] ?? [];
            $cid = $courseDetails['courseId'] ?? $course['courseId'] ?? $courseDetails['courseCode'] ?? $course['courseCode'] ?? null;
            $sec = $courseDetails['courseSection'] ?? null;
            $ay = $courseDetails['academicYear'] ?? null;
            $sem = $courseDetails['semester'] ?? null;
            if (
                $this->courseIdsMatch($cid, $courseId)
                && $this->sectionsMatch($sec, $courseSection)
                && (string) $ay === $academicYear
                && (string) $sem === $semester
            ) {
                return $course;
            }
        }

        foreach ($courses as $course) {
            $courseDetails = $course['courseDetails'] ?? [];
            $cid = $courseDetails['courseId'] ?? $course['courseId'] ?? $courseDetails['courseCode'] ?? $course['courseCode'] ?? null;
            $sec = $courseDetails['courseSection'] ?? null;
            if ($this->courseIdsMatch($cid, $courseId) && $this->sectionsMatch($sec, $courseSection)) {
                return $course;
            }
        }

        return null;
    }

    protected function courseIdsMatch(mixed $stored, string $fromSheet): bool
    {
        if ($stored === null) {
            return false;
        }

        return strcasecmp(trim((string) $stored), trim($fromSheet)) === 0;
    }

    protected function sectionsMatch(mixed $stored, string $fromSheet): bool
    {
        if ($stored === null) {
            return false;
        }

        $a = trim((string) $stored);
        $b = trim($fromSheet);
        if ($a === $b) {
            return true;
        }
        if ($a !== '' && $b !== '' && ctype_digit($a) && ctype_digit($b)) {
            return (int) $a === (int) $b;
        }

        return false;
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @return array{total: int, notStarted: int, inProgress: int, lecturerSubmitted: int, coordinatorReviewed: int}
     */
    protected function courseMonitoringStatsForLecturer(
        array $rows,
        string $academicYear,
        string $semester,
        string $email
    ): array {
        $norm = strtolower(trim($email));
        $assignments = [];
        foreach ($rows as $row) {
            $inst = $this->sheetCell($row, [
                'InstructorEmail', 'instructorEmail', 'Instructor Email', 'instructor email',
                'Email', 'email', 'LecturerEmail', 'lecturerEmail', 'Lecturer Email', 'FacultyEmail', 'facultyEmail',
            ]);
            if ($inst === null || strtolower(trim($inst)) !== $norm) {
                continue;
            }
            $assignment = $this->courseSheetSectionAssignment($row, $academicYear, $semester, true);
            if ($assignment !== null) {
                $assignments[] = $assignment;
            }
        }

        return $this->summarizeAssignments($assignments);
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @return array{total: int, notStarted: int, inProgress: int, lecturerSubmitted: int, coordinatorReviewed: int}
     */
    protected function courseMonitoringStatsForCourseCoordinator(
        array $rows,
        string $academicYear,
        string $semester,
        string $email
    ): array {
        $assignments = $this->collectCourseCoordinatorAssignments($rows, $email, $academicYear, $semester);
        $sheetSummary = $this->summarizeCourseCoordinatorAssignmentsByCourseCode($assignments);
        $storedCm = $this->loadCourseCoordinatorReportCourseMonitoring($email, $academicYear, $semester);

        return $this->mergeCourseCoordinatorDashboardStatsFromSheetAndReport($sheetSummary, $storedCm);
    }

    /**
     * Reads persisted course-coordinator aggregate for this academic year and semester.
     *
     * @return array{total?: int, notStarted?: int, inProgress?: int, lecturerSubmitted?: int, coordinatorReviewed?: int}|null
     */
    protected function loadCourseCoordinatorReportCourseMonitoring(string $ccEmail, string $academicYear, string $semester): ?array
    {
        try {
            $doc = FirestoreService::getDocument($this->courseCoordinatorReportCollection, $ccEmail);
        } catch (\Throwable $e) {
            Log::warning('Dashboard courseCoordinatorReport read failed', ['email' => $ccEmail, 'error' => $e->getMessage()]);

            return null;
        }
        if (! is_array($doc)) {
            return null;
        }
        $reports = $doc['reports'] ?? [];
        if (! is_array($reports)) {
            return null;
        }
        $wantY = trim($academicYear);
        $wantS = trim($semester);
        if ($wantY === '' && $wantS === '') {
            return null;
        }
        foreach ($reports as $r) {
            if (! is_array($r)) {
                continue;
            }
            $y = trim((string) ($r['academicYear'] ?? ''));
            $s = trim((string) ($r['semester'] ?? ''));
            if ($wantY !== '' && strcasecmp($y, $wantY) !== 0) {
                continue;
            }
            if ($wantS !== '' && strcasecmp($s, $wantS) !== 0) {
                continue;
            }
            $cm = $r['courseMonitoring'] ?? null;
            if (! is_array($cm)) {
                return null;
            }

            return $cm;
        }

        return null;
    }

    /**
     * Total unique course codes come from the sheet. When a {@see $courseCoordinatorReportCollection} snapshot
     * exists for the same coordinator + academic year + semester, status counts (not started, in progress,
     * submitted, reviewed) are taken from that snapshot and scaled so their sum matches the sheet total.
     *
     * @param  array{total: int, notStarted: int, inProgress: int, lecturerSubmitted: int, coordinatorReviewed: int}  $sheetSummary
     * @param  array<string, mixed>|null  $storedCm
     * @return array{total: int, notStarted: int, inProgress: int, lecturerSubmitted: int, coordinatorReviewed: int}
     */
    protected function mergeCourseCoordinatorDashboardStatsFromSheetAndReport(array $sheetSummary, ?array $storedCm): array
    {
        $total = max(0, (int) ($sheetSummary['total'] ?? 0));
        if ($storedCm === null) {
            return [
                'total' => $total,
                'notStarted' => max(0, (int) ($sheetSummary['notStarted'] ?? 0)),
                'inProgress' => max(0, (int) ($sheetSummary['inProgress'] ?? 0)),
                'lecturerSubmitted' => max(0, (int) ($sheetSummary['lecturerSubmitted'] ?? 0)),
                'coordinatorReviewed' => max(0, (int) ($sheetSummary['coordinatorReviewed'] ?? 0)),
            ];
        }

        $ns = max(0, (int) ($storedCm['notStarted'] ?? 0));
        $ip = max(0, (int) ($storedCm['inProgress'] ?? 0));
        $ls = max(0, (int) ($storedCm['lecturerSubmitted'] ?? 0));
        $cr = max(0, (int) ($storedCm['coordinatorReviewed'] ?? 0));
        $sum = $ns + $ip + $ls + $cr;

        if ($total === 0) {
            return $this->emptyCourseMonitoringStats();
        }
        if ($sum === 0) {
            return [
                'total' => $total,
                'notStarted' => $total,
                'inProgress' => 0,
                'lecturerSubmitted' => 0,
                'coordinatorReviewed' => 0,
            ];
        }

        $scaled = $this->scaleNonNegativeIntegersToTargetSum([$ns, $ip, $ls, $cr], $total);

        return [
            'total' => $total,
            'notStarted' => $scaled[0],
            'inProgress' => $scaled[1],
            'lecturerSubmitted' => $scaled[2],
            'coordinatorReviewed' => $scaled[3],
        ];
    }

    /**
     * @param  list<int>  $counts
     * @return list<int>
     */
    protected function scaleNonNegativeIntegersToTargetSum(array $counts, int $targetSum): array
    {
        $targetSum = max(0, $targetSum);
        $n = count($counts);
        if ($n === 0) {
            return [];
        }
        $clean = [];
        foreach ($counts as $c) {
            $clean[] = max(0, $c);
        }
        $sum = array_sum($clean);
        if ($sum === 0) {
            return array_fill(0, $n, 0);
        }
        if ($sum === $targetSum) {
            return $clean;
        }

        $out = [];
        $frac = [];
        foreach ($clean as $i => $c) {
            $exact = ($targetSum * $c) / $sum;
            $out[$i] = (int) floor($exact);
            $frac[$i] = $exact - $out[$i];
        }
        $rem = $targetSum - array_sum($out);
        while ($rem > 0) {
            $best = null;
            $bestF = -1.0;
            foreach ($frac as $i => $f) {
                if ($f > $bestF) {
                    $bestF = $f;
                    $best = $i;
                }
            }
            if ($best === null) {
                break;
            }
            $out[$best]++;
            $frac[$best] = -1.0;
            $rem--;
        }

        return array_values($out);
    }

    /**
     * One dashboard unit per **unique course code** (not per section). Status is read from each lecturer's
     * `courseMonitoring` Firestore doc for that section; the course rolls up to the weakest section state
     * (all sections must be coordinator-reviewed before the course counts as reviewed).
     *
     * @param  list<array{instructorEmail: string, courseCode: string, section: string, academicYear: string, semester: string}>  $assignments
     * @return array{total: int, notStarted: int, inProgress: int, lecturerSubmitted: int, coordinatorReviewed: int}
     */
    protected function summarizeCourseCoordinatorAssignmentsByCourseCode(array $assignments): array
    {
        return $this->buildCourseCoordinatorCourseMonitoringDetail($assignments)['summary'];
    }

    /**
     * Per-course roll-up plus summary counts for persisting to Firestore `cmon_courseCoordinatorReport`.
     *
     * @param  list<array{instructorEmail: string, courseCode: string, section: string, academicYear: string, semester: string}>  $assignments
     * @return array{summary: array{total: int, notStarted: int, inProgress: int, lecturerSubmitted: int, coordinatorReviewed: int}, courses: list<array{courseCode: string, aggregatedStatus: string, sections: list<array{instructorEmail: string, section: string, status: string}>}>}
     */
    protected function buildCourseCoordinatorCourseMonitoringDetail(array $assignments): array
    {
        $byCourse = [];
        foreach ($assignments as $a) {
            $code = trim((string) ($a['courseCode'] ?? ''));
            if ($code === '') {
                continue;
            }
            $k = strtolower($code);
            if (! isset($byCourse[$k])) {
                $byCourse[$k] = [];
            }
            $byCourse[$k][] = $a;
        }

        $stats = $this->emptyCourseMonitoringStats();
        $cache = [];
        $coursesOut = [];

        foreach ($byCourse as $sections) {
            $stats['total']++;
            $minRank = 3;
            $sectionDetails = [];
            $canonicalCode = trim((string) ($sections[0]['courseCode'] ?? ''));
            foreach ($sections as $a) {
                $lecturerEmail = trim((string) ($a['instructorEmail'] ?? ''));
                if ($lecturerEmail === '') {
                    $minRank = min($minRank, $this->courseMonitoringProgressRank('notStarted'));
                    $sectionDetails[] = [
                        'instructorEmail' => '',
                        'section' => trim((string) ($a['section'] ?? '')),
                        'status' => 'notStarted',
                    ];

                    continue;
                }
                $bucket = $this->classifySection(
                    $lecturerEmail,
                    (string) $a['courseCode'],
                    (string) $a['section'],
                    (string) $a['academicYear'],
                    (string) $a['semester'],
                    $cache
                );
                $minRank = min($minRank, $this->courseMonitoringProgressRank($bucket));
                $sectionDetails[] = [
                    'instructorEmail' => $lecturerEmail,
                    'section' => trim((string) ($a['section'] ?? '')),
                    'status' => $bucket,
                ];
            }
            $rolled = $this->courseMonitoringRankToBucketKey($minRank);
            $stats[$rolled]++;
            $coursesOut[] = [
                'courseCode' => $canonicalCode,
                'aggregatedStatus' => $rolled,
                'sections' => $sectionDetails,
            ];
        }

        return [
            'summary' => $stats,
            'courses' => $coursesOut,
        ];
    }

    /**
     * Live snapshot from the courses sheet + lecturer `courseMonitoring` documents (for API persistence).
     *
     * @return array{
     *     coordinatorEmail: string,
     *     academicYear: string,
     *     semester: string,
     *     courseMonitoring: array{total: int, notStarted: int, inProgress: int, lecturerSubmitted: int, coordinatorReviewed: int},
     *     courses: list<array{courseCode: string, aggregatedStatus: string, sections: list<array{instructorEmail: string, section: string, status: string}>}>,
     *     sectionAssignmentsCounted: int,
     *     updatedAt: string
     * }|null
     */
    public function computeCourseCoordinatorReportSnapshot(string $courseCoordinatorEmail, string $academicYear, string $semester): ?array
    {
        $spreadsheetId = env('GOOGLE_SHEET_ID');
        $range = env('COURSES_GOOGLE_SHEET_RANGE', 'courses!A1:Z2000');
        if (! $spreadsheetId) {
            return null;
        }

        $rows = $this->getDashboardCourseRows($spreadsheetId, $range, $semester, $academicYear);
        $assignments = $this->collectCourseCoordinatorAssignments($rows, $courseCoordinatorEmail, $academicYear, $semester);
        $detail = $this->buildCourseCoordinatorCourseMonitoringDetail($assignments);

        return [
            'coordinatorEmail' => $courseCoordinatorEmail,
            'academicYear' => trim($academicYear),
            'semester' => trim($semester),
            'courseMonitoring' => $detail['summary'],
            'courses' => $detail['courses'],
            'sectionAssignmentsCounted' => count($assignments),
            'updatedAt' => now()->toIso8601String(),
        ];
    }

    /**
     * Ordering for roll-up: lower = less complete (bottleneck across sections for one course).
     */
    protected function courseMonitoringProgressRank(string $bucket): int
    {
        return match ($bucket) {
            'notStarted' => 0,
            'inProgress' => 1,
            'lecturerSubmitted' => 2,
            'coordinatorReviewed' => 3,
            default => 0,
        };
    }

    /**
     * @return 'notStarted'|'inProgress'|'lecturerSubmitted'|'coordinatorReviewed'
     */
    protected function courseMonitoringRankToBucketKey(int $rank): string
    {
        return match ($rank) {
            0 => 'notStarted',
            1 => 'inProgress',
            2 => 'lecturerSubmitted',
            3 => 'coordinatorReviewed',
            default => 'notStarted',
        };
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @return array{total: int, notStarted: int, inProgress: int, lecturerSubmitted: int, coordinatorReviewed: int}
     */
    protected function courseMonitoringStatsForChair(
        array $rows,
        string $academicYear,
        string $semester,
        string $chairEmail
    ): array {
        $assignments = $this->collectChairAssignments($rows, $chairEmail, $academicYear, $semester);

        return $this->summarizeAssignments($assignments);
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @return array{total: int, notStarted: int, inProgress: int, lecturerSubmitted: int, coordinatorReviewed: int}
     */
    protected function courseMonitoringStatsForDeanOrVp(
        array $rows,
        string $academicYear,
        string $semester,
        string $email,
        string $kind
    ): array {
        $assignments = $this->collectDeanVpAssignments($rows, $email, $kind, $academicYear, $semester);

        return $this->summarizeAssignments($assignments);
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @return array{total: int, notStarted: int, inProgress: int, lecturerSubmitted: int, coordinatorReviewed: int}
     */
    protected function aggregateCourseMonitoringForSheet(
        array $rows,
        string $academicYear,
        string $semester
    ): array {
        $assignments = [];
        foreach ($rows as $row) {
            $assignment = $this->courseSheetSectionAssignment($row, $academicYear, $semester, false);
            if ($assignment !== null) {
                $assignments[] = $assignment;
            }
        }

        return $this->summarizeAssignments($assignments);
    }

    /**
     * @return list<array<string, string|null>>
     */
    protected function loadProgramCoordinatorSheetAssocRows(string $spreadsheetId, string $pcRange): array
    {
        $rows = GoogleSheetService::readSheet($spreadsheetId, $pcRange);
        if ($rows === []) {
            return [];
        }

        $headers = $rows[0];
        $out = [];
        foreach (array_slice($rows, 1) as $row) {
            $assoc = array_combine(
                $headers,
                array_slice(array_pad($row, count($headers), null), 0, count($headers))
            );
            if (is_array($assoc)) {
                $out[] = $assoc;
            }
        }

        return $out;
    }

    /**
     * Cached Firestore `reports` array for a program coordinator document id (email).
     *
     * @param  array<string, list<mixed>>  $cache
     * @return list<array<string, mixed>>
     */
    protected function programCoordinatorReportsForPcEmail(array &$cache, string $pcEmail): array
    {
        $k = strtolower(trim($pcEmail));
        if (! isset($cache[$k])) {
            try {
                $doc = FirestoreService::getDocument($this->programReportsCollection, $pcEmail);
                $reports = $doc['reports'] ?? [];
                $cache[$k] = is_array($reports) ? $reports : [];
            } catch (\Throwable $e) {
                Log::warning('Dashboard programCoordinatorReports read failed', ['email' => $pcEmail, 'error' => $e->getMessage()]);
                $cache[$k] = [];
            }
        }

        return $cache[$k];
    }

    /**
     * APMR counts from the program-coordinators sheet (program coordinator + program code on each row),
     * validated against **programCoordinatorReports** for the academic year.
     *
     * @param  (callable(array<string, string|null>): bool)|null  $includeRow When set, only rows where this returns true are counted (e.g. chair / dean / VP assignment).
     * @return array{total: int, notStarted: int, inProgress: int, submitted: int}
     */
    protected function aggregateOrganizationProgramCoordinatorForms(string $academicYear, ?callable $includeRow): array
    {
        $spreadsheetId = env('GOOGLE_SHEET_ID');
        if (! $spreadsheetId) {
            return ['total' => 0, 'notStarted' => 0, 'inProgress' => 0, 'submitted' => 0];
        }

        $pcRange = env('PROGRAM_COORDINATORS_GOOGLE_SHEET_RANGE', 'program-coordinators!A1:Z2000');
        $assocs = $this->loadProgramCoordinatorSheetAssocRows($spreadsheetId, $pcRange);
        $cache = [];
        $total = 0;
        $submitted = 0;
        $inProgress = 0;

        foreach ($assocs as $assoc) {
            if ($includeRow !== null && ! $includeRow($assoc)) {
                continue;
            }
            $pc = $this->sheetCell($assoc, [
                'programCoordinator', 'ProgramCoordinator', 'Program Coordinator', 'program coordinator',
            ]);
            $code = $this->sheetCell($assoc, ['programCode', 'ProgramCode', 'Program Code', 'program code']);
            if ($pc === null || trim($pc) === '' || $code === null || trim($code) === '') {
                continue;
            }
            $total++;
            $reports = $this->programCoordinatorReportsForPcEmail($cache, trim($pc));
            $idx = $this->findPcReportIndex($reports, trim($code), $academicYear);
            if ($idx < 0) {
                continue;
            }
            $r = $reports[$idx];
            if (! is_array($r)) {
                continue;
            }
            if (! empty($r['hasCoordinatorSubmitted'])) {
                $submitted++;
            } else {
                $inProgress++;
            }
        }

        $notStarted = max(0, $total - $inProgress - $submitted);

        return [
            'total' => $total,
            'notStarted' => $notStarted,
            'inProgress' => $inProgress,
            'submitted' => $submitted,
        ];
    }

    /**
     * Program coordinator APMR: each **row** on the program-coordinators sheet where the programCoordinator column
     * matches this user is one form to submit for the academic year. The Chair column identifies who reviews it.
     * Totals come from the sheet; initialized / submitted / pending are validated per row against Firestore
     * **programCoordinatorReports** (document id = PC email, keyed by programCode + academicYear in each report).
     *
     * @return array{programsExpected: int, reportsInitialized: int, coordinatorSubmitted: int, notSubmitted: int}
     */
    protected function programCoordinatorReportStats(string $pcEmail, string $academicYear): array
    {
        $spreadsheetId = env('GOOGLE_SHEET_ID');
        $pcRange= env('PROGRAM_COORDINATORS_GOOGLE_SHEET_RANGE', 'program-coordinators!A1:Z2000');
        if (! $spreadsheetId) {
            return [
                'programsExpected' => 0,
                'reportsInitialized' => 0,
                'coordinatorSubmitted' => 0,
                'notSubmitted' => 0,
            ];
        }

        $assocs = $this->loadProgramCoordinatorSheetAssocRows($spreadsheetId, $pcRange);
        if ($assocs === []) {
            return [
                'programsExpected' => 0,
                'reportsInitialized' => 0,
                'coordinatorSubmitted' => 0,
                'notSubmitted' => 0,
            ];
        }

        /** @var list<array{programCode: string}> $sheetForms one entry per sheet row assigned to this PC */
        $sheetForms = [];

        foreach ($assocs as $assoc) {
            $pc = $this->sheetCell($assoc, [
                'programCoordinator', 'ProgramCoordinator', 'Program Coordinator', 'program coordinator',
            ]);
            if (! $this->emailsEqual($pc, $pcEmail)) {
                continue;
            }
            $code = $this->sheetCell($assoc, ['programCode', 'ProgramCode', 'Program Code', 'program code']);
            if ($code === null || trim($code) === '') {
                continue;
            }
            $sheetForms[] = ['programCode' => trim($code)];
        }

        $cache = [];
        $reports = $this->programCoordinatorReportsForPcEmail($cache, $pcEmail);
        $initialized = 0;
        $submitted = 0;

        foreach ($sheetForms as $form) {
            $idx = $this->findPcReportIndex($reports, $form['programCode'], $academicYear);
            if ($idx >= 0) {
                $initialized++;
                $r = $reports[$idx];
                if (is_array($r) && ! empty($r['hasCoordinatorSubmitted'])) {
                    $submitted++;
                }
            }
        }

        $expected = count($sheetForms);

        return [
            'programsExpected' => $expected,
            'reportsInitialized' => $initialized,
            'coordinatorSubmitted' => $submitted,
            'notSubmitted' => max(0, $expected - $submitted),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $reports
     */
    protected function findPcReportIndex(array $reports, string $programCode, string $academicYear): int
    {
        foreach ($reports as $index => $report) {
            if (! is_array($report)) {
                continue;
            }
            $details = $report['programDetails'] ?? [];
            $candidateProgram = (string) ($details['programCode'] ?? '');
            $candidateYear = (string) ($details['academicYear'] ?? '');
            if (strcasecmp($candidateProgram, trim($programCode)) === 0 && strcasecmp($candidateYear, trim($academicYear)) === 0) {
                return (int) $index;
            }
        }

        return -1;
    }

    /**
     * Chair review of PC program reports: each **row** on program-coordinators where this user is Chair (or Chair 2)
     * is one form the assigned program coordinator submits for the chair to review. Counts follow sheet rows;
     * status is read from **programCoordinatorReports** for the PC on that row.
     *
     * @return array{programsExpected: int, pcSubmitted: int, chairApproved: int, pendingChairReview: int}
     */
    protected function chairPcProgramReviewStats(string $chairEmail, string $academicYear): array
    {
        $spreadsheetId = env('GOOGLE_SHEET_ID');
        $pcRange = env('PROGRAM_COORDINATORS_GOOGLE_SHEET_RANGE', 'program-coordinators!A1:Z2000');
        if (! $spreadsheetId) {
            return [
                'programsExpected' => 0,
                'pcSubmitted' => 0,
                'chairApproved' => 0,
                'pendingChairReview' => 0,
            ];
        }

        $assocs = $this->loadProgramCoordinatorSheetAssocRows($spreadsheetId, $pcRange);
        if ($assocs === []) {
            return [
                'programsExpected' => 0,
                'pcSubmitted' => 0,
                'chairApproved' => 0,
                'pendingChairReview' => 0,
            ];
        }

        /** @var list<array{pc: string, programCode: string}> $formRows */
        $formRows = [];

        foreach ($assocs as $assoc) {
            $chair1 = $this->sheetCell($assoc, ['Chair']);
            $chair2 = $this->sheetCell($assoc, ['Chair 2', 'Chair2', 'chair2']);
            if (! $this->emailsEqual($chair1, $chairEmail) && ! $this->emailsEqual($chair2, $chairEmail)) {
                continue;
            }
            $pc = $this->sheetCell($assoc, [
                'programCoordinator', 'ProgramCoordinator', 'Program Coordinator', 'program coordinator',
            ]);
            $code = $this->sheetCell($assoc, ['programCode', 'ProgramCode', 'Program Code', 'program code']);
            if ($pc === null || $code === null || trim($code) === '') {
                continue;
            }
            $formRows[] = ['pc' => trim($pc), 'programCode' => trim($code)];
        }

        $pcSubmitted = 0;
        $chairApproved = 0;
        $pending = 0;
        $cache = [];

        foreach ($formRows as $pair) {
            $reports = $this->programCoordinatorReportsForPcEmail($cache, $pair['pc']);
            $idx = $this->findPcReportIndex($reports, $pair['programCode'], $academicYear);
            if ($idx < 0) {
                continue;
            }
            $r = $reports[$idx];
            if (! is_array($r)) {
                continue;
            }
            if (empty($r['hasCoordinatorSubmitted'])) {
                continue;
            }
            $pcSubmitted++;
            if (! empty($r['hasChairApproved'])) {
                $chairApproved++;
            } else {
                $pending++;
            }
        }

        return [
            'programsExpected' => count($formRows),
            'pcSubmitted' => $pcSubmitted,
            'chairApproved' => $chairApproved,
            'pendingChairReview' => $pending,
        ];
    }

    /**
     * Chair APVR: one report obligation per chair per academic year, stored in `cmon_ChairAnnualProgramValidationReport`.
     * Response keys match the dashboard UI; {@see programsExpected} is always 1 (single annual report slot).
     *
     * @return array{programsExpected: int, reportsSaved: int, submitted: int, notSubmitted: int}
     */
    protected function chairApvrStats(string $chairEmail, string $academicYear): array
    {
        $doc = FirestoreService::getDocument($this->chairApvrCollection, $chairEmail);
        $reports = is_array($doc) ? ($doc['reports'] ?? []) : [];

        $matchingYear = 0;
        $submittedCount = 0;
        foreach ($reports as $r) {
            if (! is_array($r)) {
                continue;
            }
            $h = $r['header'] ?? [];
            $y = (string) ($h['academicYearAppliesTo'] ?? '');
            if ($y === '' || strcasecmp(trim($y), trim($academicYear)) !== 0) {
                continue;
            }
            $matchingYear++;
            $sd = (string) ($h['submissionDate'] ?? '');
            if (trim($sd) !== '') {
                $submittedCount++;
            }
        }

        $expected = 1;
        $saved = $matchingYear > 0 ? 1 : 0;
        $submitted = $submittedCount > 0 ? 1 : 0;

        return [
            'programsExpected' => $expected,
            'reportsSaved' => $saved,
            'submitted' => $submitted,
            'notSubmitted' => $submitted === 1 ? 0 : 1,
        ];
    }

    /**
     * Institution-wide chair workload: all program-coordinator rows with a Chair (or Chair 2) assigned,
     * plus one APVR slot per distinct chair email on that sheet (Firestore `cmon_ChairAnnualProgramValidationReport`).
     *
     * @return array{
     *     chairProgramMonitoringReview: array{programsExpected: int, pcSubmitted: int, chairApproved: int, pendingChairReview: int},
     *     chairAnnualProgramValidation: array{programsExpected: int, reportsSaved: int, submitted: int, notSubmitted: int}
     * }
     */
    protected function aggregateOrganizationChairProgramReportsAndApvr(string $academicYear): array
    {
        $emptyPc = [
            'programsExpected' => 0,
            'pcSubmitted' => 0,
            'chairApproved' => 0,
            'pendingChairReview' => 0,
        ];
        $emptyApvr = [
            'programsExpected' => 0,
            'reportsSaved' => 0,
            'submitted' => 0,
            'notSubmitted' => 0,
        ];

        $spreadsheetId = env('GOOGLE_SHEET_ID');
        $pcRange = env('PROGRAM_COORDINATORS_GOOGLE_SHEET_RANGE', 'program-coordinators!A1:Z2000');
        if (! $spreadsheetId) {
            return ['chairProgramMonitoringReview' => $emptyPc, 'chairAnnualProgramValidation' => $emptyApvr];
        }

        $assocs = $this->loadProgramCoordinatorSheetAssocRows($spreadsheetId, $pcRange);
        if ($assocs === []) {
            return ['chairProgramMonitoringReview' => $emptyPc, 'chairAnnualProgramValidation' => $emptyApvr];
        }

        /** @var list<array{pc: string, programCode: string}> $formRows */
        $formRows = [];
        /** @var array<string, true> $chairEmails */
        $chairEmails = [];

        foreach ($assocs as $assoc) {
            $chair1 = $this->sheetCell($assoc, ['Chair']);
            $chair2 = $this->sheetCell($assoc, ['Chair 2', 'Chair2', 'chair2']);
            $hasChair = ($chair1 !== null && trim($chair1) !== '') || ($chair2 !== null && trim($chair2) !== '');
            if ($hasChair) {
                foreach ([$chair1, $chair2] as $ce) {
                    if ($ce !== null && trim($ce) !== '') {
                        $chairEmails[strtolower(trim($ce))] = true;
                    }
                }
            }
            if (! $hasChair) {
                continue;
            }
            $pc = $this->sheetCell($assoc, [
                'programCoordinator', 'ProgramCoordinator', 'Program Coordinator', 'program coordinator',
            ]);
            $code = $this->sheetCell($assoc, ['programCode', 'ProgramCode', 'Program Code', 'program code']);
            if ($pc === null || $code === null || trim($code) === '') {
                continue;
            }
            $formRows[] = ['pc' => trim($pc), 'programCode' => trim($code)];
        }

        $pcSubmitted = 0;
        $chairApproved = 0;
        $pending = 0;
        $cache = [];

        foreach ($formRows as $pair) {
            $reports = $this->programCoordinatorReportsForPcEmail($cache, $pair['pc']);
            $idx = $this->findPcReportIndex($reports, $pair['programCode'], $academicYear);
            if ($idx < 0) {
                continue;
            }
            $r = $reports[$idx];
            if (! is_array($r)) {
                continue;
            }
            if (empty($r['hasCoordinatorSubmitted'])) {
                continue;
            }
            $pcSubmitted++;
            if (! empty($r['hasChairApproved'])) {
                $chairApproved++;
            } else {
                $pending++;
            }
        }

        $apvrExpected = 0;
        $apvrSaved = 0;
        $apvrSubmitted = 0;
        foreach (array_keys($chairEmails) as $chairEmail) {
            $apvrExpected++;
            $s = $this->chairApvrStats($chairEmail, $academicYear);
            $apvrSaved += (int) ($s['reportsSaved'] ?? 0);
            $apvrSubmitted += (int) ($s['submitted'] ?? 0);
        }

        return [
            'chairProgramMonitoringReview' => [
                'programsExpected' => count($formRows),
                'pcSubmitted' => $pcSubmitted,
                'chairApproved' => $chairApproved,
                'pendingChairReview' => $pending,
            ],
            'chairAnnualProgramValidation' => [
                'programsExpected' => $apvrExpected,
                'reportsSaved' => $apvrSaved,
                'submitted' => $apvrSubmitted,
                'notSubmitted' => max(0, $apvrExpected - $apvrSubmitted),
            ],
        ];
    }

    /**
     * Normalize sheet header labels so "Course Code", " course code ", and UTF-8 BOM-prefixed headers match.
     */
    protected function normalizeSheetColumnAlias(mixed $label): string
    {
        if (! is_string($label) && ! is_int($label) && ! is_float($label)) {
            return '';
        }
        $s = trim((string) $label);
        if ($s !== '' && str_starts_with($s, "\xEF\xBB\xBF")) {
            $s = trim(substr($s, 3));
        }
        $s = strtolower($s);
        $s = preg_replace('/\s+/', '', $s) ?? '';
        $s = str_replace('_', '', $s);

        return $s;
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  list<string>  $keys
     */
    protected function sheetCell(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                $v = $row[$key];
                if ($v !== null && trim((string) $v) !== '') {
                    return trim((string) $v);
                }
            }
        }

        foreach ($keys as $key) {
            $want = $this->normalizeSheetColumnAlias($key);
            if ($want === '') {
                continue;
            }
            foreach ($row as $rk => $v) {
                if ($this->normalizeSheetColumnAlias($rk) !== $want) {
                    continue;
                }
                if ($v !== null && trim((string) $v) !== '') {
                    return trim((string) $v);
                }
            }
        }

        return null;
    }

    protected function emailsEqual(?string $a, ?string $b): bool
    {
        if ($a === null || $b === null || $a === '' || $b === '') {
            return false;
        }

        return strtolower(trim($a)) === strtolower(trim($b));
    }
}
