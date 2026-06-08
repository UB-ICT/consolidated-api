<?php

namespace Modules\Xenegrade\Http\Controllers;

use App\Services\CourseEvaluationAggregator;
use App\Services\FirestoreService;
use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class AggregatedCourseMonitoringController extends Controller
{
    protected string $collectionName = 'cmon_courseMonitoring';

    public function listCourses(Request $request, string $email, string $academicYear, string $semester)
    {
        $validator = Validator::make(
            array_merge($request->query(), ['email' => $email, 'academicYear' => $academicYear, 'semester' => $semester]),
            [
                'email' => 'required|email',
                'academicYear' => 'required|string',
                'semester' => 'required|string',
                'role' => 'required|string|in:courseCoordinator,programCoordinator',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $spreadsheetId = env('GOOGLE_SHEET_ID');
        $range = env('COURSES_GOOGLE_SHEET_RANGE', 'courses!A1:Z2000');
        if (! $spreadsheetId) {
            return response()->json([
                'success' => false,
                'message' => 'Google Sheet ID not configured.',
            ], 500);
        }

        $role = $request->query('role');
        $assignments = $this->collectSheetAssignments($spreadsheetId, $range, $semester, (string) $role, $email);
        $grouped = $this->groupAssignmentsByCourseCode($assignments);
        $courses = [];

        foreach ($grouped as $courseCode => $meta) {
            $sectionsOut = [];
            $allReady = true;
            foreach ($meta['sections'] as $sec) {
                $st = $this->loadSectionEvaluationStatus(
                    $sec['instructorEmail'],
                    $courseCode,
                    $sec['section'],
                    $academicYear,
                    $semester
                );
                $sectionsOut[] = array_merge($sec, $st);
                if (! $st['lecturerSubmitted'] || ! $st['coordinatorReviewed']) {
                    $allReady = false;
                }
            }
            $courses[] = [
                'courseCode' => $courseCode,
                'courseTitle' => $meta['courseTitle'] ?: $courseCode,
                'sections' => $sectionsOut,
                'canFill' => $allReady && count($sectionsOut) > 0,
            ];
        }

        usort($courses, function ($a, $b) {
            return strcasecmp((string) ($a['courseCode'] ?? ''), (string) ($b['courseCode'] ?? ''));
        });

        return response()->json([
            'success' => true,
            'data' => $courses,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'role' => $role,
        ]);
    }

    public function getAggregatedCourse(Request $request, string $email, string $courseCode, string $academicYear, string $semester)
    {
        $validator = Validator::make(
            array_merge($request->query(), [
                'email' => $email,
                'courseCode' => $courseCode,
                'academicYear' => $academicYear,
                'semester' => $semester,
            ]),
            [
                'email' => 'required|email',
                'courseCode' => 'required|string',
                'academicYear' => 'required|string',
                'semester' => 'required|string',
                'role' => 'required|string|in:courseCoordinator,programCoordinator',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $spreadsheetId = env('GOOGLE_SHEET_ID');
        $range = env('COURSES_GOOGLE_SHEET_RANGE', 'courses!A1:Z2000');
        if (! $spreadsheetId) {
            return response()->json([
                'success' => false,
                'message' => 'Google Sheet ID not configured.',
            ], 500);
        }

        $role = (string) $request->query('role');
        $assignments = $this->collectSheetAssignments($spreadsheetId, $range, $semester, $role, $email);
        $sections = array_values(array_filter($assignments, function ($a) use ($courseCode) {
            return strcasecmp(trim($a['courseCode']), trim($courseCode)) === 0;
        }));

        if ($sections === []) {
            return response()->json([
                'success' => false,
                'message' => 'No sheet assignments found for this course.',
            ], 404);
        }

        $allReady = true;
        $sectionPayloads = [];
        foreach ($sections as $sec) {
            $st = $this->loadSectionEvaluationStatus(
                $sec['instructorEmail'],
                $courseCode,
                $sec['section'],
                $academicYear,
                $semester
            );
            if (! $st['lecturerSubmitted'] || ! $st['coordinatorReviewed']) {
                $allReady = false;
            }
            if ($st['course'] !== null) {
                $label = 'Section ' . $sec['section'] . ' — ' . $sec['instructorEmail'];
                $sectionPayloads[] = ['label' => $label, 'course' => $st['course']];
            }
        }

        if (! $allReady) {
            return response()->json([
                'success' => false,
                'message' => 'This course is not ready for aggregate monitoring. All sections must be submitted by lecturers and reviewed by the course coordinator.',
                'code' => 'PREREQUISITES_NOT_MET',
            ], 409);
        }

        if ($sectionPayloads === []) {
            return response()->json([
                'success' => false,
                'message' => 'No submitted section evaluations were found to merge.',
            ], 404);
        }

        $existingAggregate = $this->loadAggregateFromViewerDoc($email, $courseCode, $academicYear, $semester);
        if ($existingAggregate && ! empty($existingAggregate['hasLecturerSubmitted'])) {
            return response()->json([
                'success' => true,
                'data' => $existingAggregate,
                'meta' => [
                    'canEdit' => false,
                    'aggregateSectionId' => CourseEvaluationAggregator::AGGREGATE_SECTION_ID,
                    'sections' => $sections,
                ],
            ]);
        }

        $merged = CourseEvaluationAggregator::merge($sectionPayloads);
        $merged['courseDetails'] = $merged['courseDetails'] ?? [];
        $merged['courseDetails']['courseSection'] = CourseEvaluationAggregator::AGGREGATE_SECTION_ID;
        $merged['courseDetails']['courseCode'] = trim($courseCode);
        $merged['courseDetails']['courseId'] = trim($courseCode);
        $merged['courseDetails']['academicYear'] = $academicYear;
        $merged['courseDetails']['semester'] = $semester;
        $merged['courseDetails']['courseInstructorEmail'] = $email;
        $merged['courseDetails']['isAggregateCourseMonitoring'] = true;
        $merged['courseDetails']['aggregateSourceRole'] = $role;
        $title = (string) ($merged['courseDetails']['courseTitle'] ?? $merged['courseDetails']['courseName'] ?? '');
        if ($title !== '' && stripos($title, '(all sections)') === false) {
            $merged['courseDetails']['courseTitle'] = $title . ' (all sections)';
            $merged['courseDetails']['courseName'] = $merged['courseDetails']['courseTitle'];
        }
        $merged['hasLecturerSubmitted'] = false;
        $merged['hasCoordinatorApproved'] = false;
        $merged['courseId'] = trim($courseCode);

        $this->upsertAggregateCourseOnViewerDoc($email, $merged);

        return response()->json([
            'success' => true,
            'data' => $merged,
            'meta' => [
                'canEdit' => true,
                'aggregateSectionId' => CourseEvaluationAggregator::AGGREGATE_SECTION_ID,
                'sections' => $sections,
            ],
        ]);
    }

    /**
     * @return list<array{courseCode: string, section: string, instructorEmail: string, courseTitle: string, totalSections: int}>
     */
    private function collectSheetAssignments(
        string $spreadsheetId,
        string $range,
        string $semester,
        string $role,
        string $viewerEmail
    ): array {
        $rows = GoogleSheetService::getRowsAssocForSemester($spreadsheetId, $range, $semester);
        $out = [];

        foreach ($rows as $row) {
            $courseCode = $this->cell($row, ['CourseCode', 'courseCode', 'Course Code', 'CourseID', 'courseId']);
            $section = $this->cell($row, ['CourseSection', 'courseSection', 'Course Section', 'SectionID', 'sectionId', 'Section']);
            $inst = $this->cell($row, ['InstructorEmail', 'instructorEmail', 'Instructor Email']);
            $title = $this->cell($row, ['CourseName', 'courseName', 'Course Title', 'CourseTitle', 'courseTitle']);
            $cc = $this->cell($row, ['CourseCoordinator', 'courseCoordinator', 'Course Coordinator']);
            $pc = $this->cell($row, ['ProgramCoordinator', 'programCoordinator', 'Program Coordinator']);
            $totalSectionsRaw = $this->cell($row, ['TotalSections', 'totalSections', 'Total Sections']);
            $totalSections = is_numeric((string) $totalSectionsRaw) ? (int) $totalSectionsRaw : 0;

            if ($courseCode === '' || $section === '' || $inst === '') {
                continue;
            }

            if (
                $role === 'courseCoordinator'
                && $this->emailsEqual($cc, $viewerEmail)
                && $totalSections !== 1
            ) {
                $out[] = [
                    'courseCode' => trim($courseCode),
                    'section' => trim($section),
                    'instructorEmail' => trim($inst),
                    'courseTitle' => $title ? trim($title) : '',
                    'totalSections' => $totalSections,
                ];
            }

            if (
                $role === 'programCoordinator'
                && $this->emailsEqual($pc, $viewerEmail)
                && (($cc === null || $cc === '') || $totalSections === 1)
            ) {
                $out[] = [
                    'courseCode' => trim($courseCode),
                    'section' => trim($section),
                    'instructorEmail' => trim($inst),
                    'courseTitle' => $title ? trim($title) : '',
                    'totalSections' => $totalSections,
                ];
            }
        }

        return $out;
    }

    /**
     * @param  list<array{courseCode: string, section: string, instructorEmail: string, courseTitle: string, totalSections: int}>  $assignments
     * @return array<string, array{courseTitle: string, sections: list<array{section: string, instructorEmail: string}>}>
     */
    private function groupAssignmentsByCourseCode(array $assignments): array
    {
        $byLower = [];
        foreach ($assignments as $a) {
            $key = strtolower($a['courseCode']);
            if (! isset($byLower[$key])) {
                $byLower[$key] = [
                    'canonicalCode' => $a['courseCode'],
                    'courseTitle' => $a['courseTitle'] ?? '',
                    'sections' => [],
                ];
            }
            if (($byLower[$key]['courseTitle'] ?? '') === '' && ($a['courseTitle'] ?? '') !== '') {
                $byLower[$key]['courseTitle'] = $a['courseTitle'];
            }
            $dup = false;
            foreach ($byLower[$key]['sections'] as $existing) {
                if ($existing['section'] === $a['section'] && $this->emailsEqual($existing['instructorEmail'], $a['instructorEmail'])) {
                    $dup = true;
                    break;
                }
            }
            if (! $dup) {
                $byLower[$key]['sections'][] = [
                    'section' => $a['section'],
                    'instructorEmail' => $a['instructorEmail'],
                ];
            }
        }

        $canonical = [];
        foreach ($byLower as $meta) {
            $canonical[$meta['canonicalCode']] = [
                'courseTitle' => $meta['courseTitle'],
                'sections' => $meta['sections'],
            ];
        }

        return $canonical;
    }

    /**
     * @return array{lecturerSubmitted: bool, coordinatorReviewed: bool, course: ?array<string, mixed>}
     */
    private function loadSectionEvaluationStatus(
        string $instructorEmail,
        string $courseCode,
        string $section,
        string $academicYear,
        string $semester
    ): array {
        $doc = FirestoreService::getDocument($this->collectionName, $instructorEmail);
        if ($doc === null) {
            return ['lecturerSubmitted' => false, 'coordinatorReviewed' => false, 'course' => null];
        }
        $courses = $doc['courses'] ?? [];
        if (! is_array($courses)) {
            return ['lecturerSubmitted' => false, 'coordinatorReviewed' => false, 'course' => null];
        }
        $idx = $this->findCourseIndex($courses, $courseCode, $section, $academicYear, $semester);
        if ($idx === -1) {
            return ['lecturerSubmitted' => false, 'coordinatorReviewed' => false, 'course' => null];
        }
        $c = $courses[$idx];
        $submitted = ! empty($c['hasLecturerSubmitted']);
        $reviewed = ! empty($c['hasCoordinatorApproved']);

        return [
            'lecturerSubmitted' => $submitted,
            'coordinatorReviewed' => $reviewed,
            'course' => $submitted ? $c : null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $courses
     */
    private function findCourseIndex(array $courses, string $courseId, string $courseSection, string $academicYear, string $semester): int
    {
        foreach ($courses as $index => $course) {
            $courseDetails = $course['courseDetails'] ?? [];
            if (
                ($courseDetails['courseId'] ?? $course['courseId'] ?? $courseDetails['courseCode'] ?? $course['courseCode'] ?? null) === $courseId
                && ($courseDetails['courseSection'] ?? null) === $courseSection
                && ($courseDetails['academicYear'] ?? null) === $academicYear
                && ($courseDetails['semester'] ?? null) === $semester
            ) {
                return $index;
            }
        }

        return -1;
    }

    private function loadAggregateFromViewerDoc(string $viewerEmail, string $courseCode, string $academicYear, string $semester): ?array
    {
        $doc = FirestoreService::getDocument($this->collectionName, $viewerEmail);
        if ($doc === null) {
            return null;
        }
        $courses = $doc['courses'] ?? [];
        if (! is_array($courses)) {
            return null;
        }
        $idx = $this->findCourseIndex(
            $courses,
            trim($courseCode),
            CourseEvaluationAggregator::AGGREGATE_SECTION_ID,
            $academicYear,
            $semester
        );
        if ($idx === -1) {
            return null;
        }

        return $courses[$idx];
    }

    /**
     * @param  array<string, mixed>  $merged
     */
    private function upsertAggregateCourseOnViewerDoc(string $viewerEmail, array $merged): void
    {
        $firestore = FirestoreService::firestore();
        $docRef = $firestore->collection($this->collectionName)->document($viewerEmail);
        $snapshot = $docRef->snapshot();

        if (! $snapshot->exists()) {
            $docRef->set([
                'email' => $viewerEmail,
                'courses' => [$merged],
            ], ['merge' => true]);

            return;
        }

        $data = $snapshot->data();
        $courses = $data['courses'] ?? [];
        if (! is_array($courses)) {
            $courses = [];
        }

        $cd = $merged['courseDetails'] ?? [];
        $courseCode = (string) ($cd['courseCode'] ?? '');
        $academicYear = (string) ($cd['academicYear'] ?? '');
        $semester = (string) ($cd['semester'] ?? '');
        $idx = $this->findCourseIndex(
            $courses,
            $courseCode,
            CourseEvaluationAggregator::AGGREGATE_SECTION_ID,
            $academicYear,
            $semester
        );

        if ($idx === -1) {
            $courses[] = $merged;
        } else {
            $courses[$idx] = $merged;
        }

        $docRef->set([
            'email' => $viewerEmail,
            'courses' => $courses,
        ], ['merge' => true]);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $keys
     */
    private function cell(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== null && trim((string) $row[$key]) !== '') {
                return trim((string) $row[$key]);
            }
        }

        return null;
    }

    private function emailsEqual(?string $a, ?string $b): bool
    {
        if ($a === null || $b === null || $a === '' || $b === '') {
            return false;
        }

        return strtolower(trim($a)) === strtolower(trim($b));
    }
}
