<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;

class GoogleSheetService
{
    protected static $service;

    private final function __construct()
    {
    }

    protected static function initializeSheets()
    {
        if (is_null(self::$service)) {
            $client = new Client();
            $client->setAuthConfig(config('google.service_account_key_path'));
            $client->addScope(Sheets::SPREADSHEETS_READONLY);
            self::$service = new Sheets($client);
        }
    }

    public static function readSheet(string $spreadsheetId, string $range): array
    {
        try {
            self::initializeSheets();
            $response = self::$service->spreadsheets_values->get($spreadsheetId, $range);
            return $response->getValues() ?? [];
        } catch (\Exception $e) {
            Log::error('Google Sheets read error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Filter rows by courseCode, courseSection, and semester
     */
    public static function getRowsByCourse(string $spreadsheetId, string $range, string $courseCode, string $courseSection, ?string $semester = null): array
    {
        $rows = self::readSheet($spreadsheetId, $range);

        if (empty($rows)) {
            return [];
        }

        $headers = $rows[0];
        $dataRows = array_slice($rows, 1);

        $filtered = [];
        foreach ($dataRows as $row) {
            // pad row if shorter than headers
            $rowAssoc = array_combine(
                $headers,
                array_slice(array_pad($row, count($headers), null), 0, count($headers))
            );

            // Check courseCode and courseSection match
            $matchesCourse = isset($rowAssoc['CourseCode'], $rowAssoc['CourseSection']) &&
                $rowAssoc['CourseCode'] == $courseCode &&
                $rowAssoc['CourseSection'] == $courseSection;

            // If semester is provided, also check semester match
            if ($matchesCourse) {
                if ($semester !== null) {
                    // Check if semester column exists and matches
                    $semesterColumn = $rowAssoc['Semester'] ?? $rowAssoc['semester'] ?? null;
                    if ($semesterColumn == $semester) {
                        $filtered[] = $rowAssoc;
                    }
                } else {
                    // If no semester filter, include the row
                    $filtered[] = $rowAssoc;
                }
            }
        }

        return $filtered;
    }

    /**
     * Check email across multiple columns and return role flags
     */
    public static function checkEmailRoles(string $spreadsheetId, string $range, string $email): array
    {
        $isVP = false;
        if ($email == 'senrique.munoz@ub.edu.bz' || $email == 'luis.herrera@ub.edu.bz' || $email == 'mteck@ub.edu.bz') {
            $isVP = true;
        }

        $defaultResult = [
            'lecturer' => false,
            'courseCoordinator' => false,
            'programCoordinator' => false,
            'chair' => false,
            'dean' => false,
            'VP' => $isVP,
        ];

        if (empty($spreadsheetId)) {
            Log::error('Google Sheet ID is empty in checkEmailRoles', ['email' => $email]);
            return $defaultResult;
        }

        $rows = self::readSheet($spreadsheetId, $range);

        if (empty($rows)) {
            return $defaultResult;
        }

        // Initialize result array
        $result = $defaultResult;
        $headers = $rows[0];
        $dataRows = array_slice($rows, 1);

        // Find column indices
        $instructorEmailIndex = array_search('InstructorEmail', $headers);
        $courseCoordinatorIndex = array_search('CourseCoordinator', $headers);
        $programCoordinatorIndex = array_search('ProgramCoordinator', $headers);
        $chairIndex = array_search('Chair', $headers);
        $chair2Index = array_search('Chair 2', $headers);
        $deanIndex = array_search('Dean', $headers);

        // Check each row for the email
        foreach ($dataRows as $row) {
            // Pad row to match headers length
            $paddedRow = array_pad($row, count($headers), null);

            // Check InstructorEmail column
            if ($instructorEmailIndex !== false && 
                isset($paddedRow[$instructorEmailIndex]) && 
                strtolower(trim($paddedRow[$instructorEmailIndex])) === strtolower(trim($email))) {
                $result['lecturer'] = true;
            }

            // Check CourseCoordinator column
            if ($courseCoordinatorIndex !== false && 
                isset($paddedRow[$courseCoordinatorIndex]) && 
                strtolower(trim($paddedRow[$courseCoordinatorIndex])) === strtolower(trim($email))) {
                $result['courseCoordinator'] = true;
            }

            // Check ProgramCoordinator column
            if ($programCoordinatorIndex !== false && 
                isset($paddedRow[$programCoordinatorIndex]) && 
                strtolower(trim($paddedRow[$programCoordinatorIndex])) === strtolower(trim($email))) {
                $result['programCoordinator'] = true;
            }

            // Check Chair column
            if ($chairIndex !== false && 
                isset($paddedRow[$chairIndex]) && 
                strtolower(trim($paddedRow[$chairIndex])) === strtolower(trim($email))) {
                $result['chair'] = true;
            }

            // Check Chair 2 column
            if ($chair2Index !== false && 
                isset($paddedRow[$chair2Index]) && 
                strtolower(trim($paddedRow[$chair2Index])) === strtolower(trim($email))) {
                $result['chair'] = true;
            }

            // Check Dean column
            if ($deanIndex !== false && 
                isset($paddedRow[$deanIndex]) && 
                strtolower(trim($paddedRow[$deanIndex])) === strtolower(trim($email))) {
                $result['dean'] = true;
            }
        }

        return $result;
    }

    /**
     * Find a course entry inside a Firestore courseEvaluation document's `courses` array.
     * Matches {@see \Modules\Xenegrade\Http\Controllers\CourseEvaluationController::findCourseIndex} semantics.
     *
     * @param  list<array<string, mixed>>  $courses
     * @return array<string, mixed>|null
     */
    private static function findCourseEvaluationEntryInCourses(
        array $courses,
        string $courseId,
        string $courseSection,
        ?string $academicYear,
        ?string $semester
    ): ?array {
        $hasFullCriteria = ($academicYear !== null && $academicYear !== '')
            && ($semester !== null && $semester !== '');

        if ($hasFullCriteria) {
            foreach ($courses as $course) {
                $courseDetails = $course['courseDetails'] ?? [];
                $cid = $courseDetails['courseId'] ?? $course['courseId'] ?? $courseDetails['courseCode'] ?? $course['courseCode'] ?? null;
                if (
                    $cid === $courseId
                    && ($courseDetails['courseSection'] ?? null) === $courseSection
                    && ($courseDetails['academicYear'] ?? null) === $academicYear
                    && ($courseDetails['semester'] ?? null) === $semester
                ) {
                    return $course;
                }
            }

            return null;
        }

        foreach ($courses as $course) {
            $courseDetails = $course['courseDetails'] ?? [];
            $cid = $courseDetails['courseId'] ?? $course['courseId'] ?? $courseDetails['courseCode'] ?? $course['courseCode'] ?? null;
            if ($cid === $courseId && ($courseDetails['courseSection'] ?? null) === $courseSection) {
                return $course;
            }
        }

        return null;
    }

    /**
     * Load lecturer's courseEvaluation doc and return the matching course entry, if any.
     *
     * @return array<string, mixed>|null
     */
    private static function getCourseEvaluationFromFirestoreForInstructor(
        string $instructorEmail,
        string $courseCode,
        string $courseSection,
        ?string $academicYear,
        ?string $semester
    ): ?array {
        $instructorEmail = trim($instructorEmail);
        if ($instructorEmail === '') {
            return null;
        }

        try {
            $doc = FirestoreService::getDocument('cmon_courseMonitoring', $instructorEmail);
        } catch (\Throwable $e) {
            Log::warning('Firestore lookup failed in getCoursesByCoordinatorEmail', [
                'email' => $instructorEmail,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if ($doc === null) {
            return null;
        }

        $courses = $doc['courses'] ?? [];
        if (! is_array($courses) || $courses === []) {
            return null;
        }

        return self::findCourseEvaluationEntryInCourses(
            $courses,
            $courseCode,
            $courseSection,
            $academicYear,
            $semester
        );
    }

    /**
     * Ensure list responses include top-level keys used by API consumers alongside nested Firestore data.
     *
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    private static function enrichCoordinatorCourseListItemFromFirestore(
        array $entry,
        string $fallbackCourseCode,
        string $fallbackSection,
        ?string $fallbackTitle,
        ?string $instructorEmail
    ): array {
        $details = $entry['courseDetails'] ?? [];
        if (! isset($entry['instructorEmail']) || $entry['instructorEmail'] === '') {
            $entry['instructorEmail'] = $details['courseInstructorEmail'] ?? $instructorEmail ?? '';
        }
        if (! isset($entry['courseCode']) || $entry['courseCode'] === '') {
            $entry['courseCode'] = $details['courseCode'] ?? $fallbackCourseCode;
        }
        if (! isset($entry['courseId']) || $entry['courseId'] === '') {
            $entry['courseId'] = $details['courseId'] ?? $details['courseCode'] ?? $fallbackCourseCode;
        }
        if (! isset($entry['courseSection']) || $entry['courseSection'] === '') {
            $entry['courseSection'] = $details['courseSection'] ?? $fallbackSection;
        }
        if (! isset($entry['courseTitle']) || $entry['courseTitle'] === '') {
            $entry['courseTitle'] = $details['courseName'] ?? $details['courseTitle'] ?? ($fallbackTitle ?? '');
        }

        return $entry;
    }

    /**
     * Get courses by course coordinator email
     * Returns courseCode, courseId (alias for courseCode), courseSection, and courseName (as courseTitle).
     * When a matching Firestore courseEvaluation entry exists for the row's instructor, returns that full record
     * (with top-level list fields filled from courseDetails when missing).
     */
    public static function getCoursesByCoordinatorEmail(string $spreadsheetId, string $range, string $coordinatorEmail): array
    {
        $rows = self::readSheet($spreadsheetId, $range);

        if (empty($rows)) {
            return [];
        }

        $headers = $rows[0];
        $dataRows = array_slice($rows, 1);

        $courses = [];
        $seenCourses = []; // Track unique course combinations to avoid duplicates

        foreach ($dataRows as $row) {
            // Pad row if shorter than headers
            $rowAssoc = array_combine(
                $headers,
                array_slice(array_pad($row, count($headers), null), 0, count($headers))
            );

            // Helper function to get value with multiple possible column name variations
            $getValue = function($data, $possibleKeys) {
                foreach ($possibleKeys as $key) {
                    if (isset($data[$key]) && !empty($data[$key])) {
                        return trim($data[$key]);
                    }
                }
                return null;
            };

            // Get course coordinator email (check multiple possible column names)
            $coordinatorEmailValue = $getValue($rowAssoc, ['CourseCoordinator', 'courseCoordinator', 'Course Coordinator']);

            // Check if this row matches the coordinator email
            if ($coordinatorEmailValue && strtolower(trim($coordinatorEmailValue)) === strtolower(trim($coordinatorEmail))) {
                // Get course fields
                $courseCode = $getValue($rowAssoc, ['CourseCode', 'courseCode', 'Course Code']);
                $courseSection = $getValue($rowAssoc, ['CourseSection', 'courseSection', 'Course Section']);
                $courseName = $getValue($rowAssoc, ['CourseName', 'courseName', 'Course Name', 'CourseTitle', 'courseTitle', 'Course Title']);
                $instructorEmail = $getValue($rowAssoc, ['InstructorEmail', 'instructorEmail', 'Instructor Email']);
                $academicYear = $getValue($rowAssoc, ['AcademicYear', 'academicYear', 'Academic Year']);
                $semester = $getValue($rowAssoc, ['Semester', 'semester']);

                // Create unique key for this course combination
                $uniqueKey = $courseCode . '|' . $courseSection;

                // Only add if we haven't seen this combination before
                if ($courseCode && $courseSection && !isset($seenCourses[$uniqueKey])) {
                    $seenCourses[$uniqueKey] = true;

                    $firebaseCourse = null;
                    if ($instructorEmail) {
                        $firebaseCourse = self::getCourseEvaluationFromFirestoreForInstructor(
                            $instructorEmail,
                            $courseCode,
                            $courseSection,
                            $academicYear ?: null,
                            $semester ?: null
                        );
                    }

                    if ($firebaseCourse !== null) {
                        $courses[] = self::enrichCoordinatorCourseListItemFromFirestore(
                            $firebaseCourse,
                            $courseCode,
                            $courseSection,
                            $courseName,
                            $instructorEmail
                        );
                    } else {
                        $courses[] = [
                            'courseCode' => $courseCode,
                            'courseId' => $courseCode, // Alias for courseCode
                            'courseSection' => $courseSection,
                            'courseTitle' => $courseName ?? '', // courseName as courseTitle
                            'instructorEmail' => $instructorEmail ?? '',
                        ];
                    }
                }
            }
        }

        return $courses;
    }

    /**
     * Get courses by program coordinator email
     * Returns courseCode, courseId (alias for courseCode), courseSection, and courseName (as courseTitle).
     * Includes only rows where CourseCoordinator is empty and the program coordinator email matches.
     * Restricts to offerings with exactly one distinct section (same course code + academic year + semester).
     * When a matching Firestore courseEvaluation entry exists for the row's instructor, returns that enriched record.
     */
    public static function getCoursesByProgramCoordinatorEmail(string $spreadsheetId, string $range, string $programCoordinatorEmail): array
    {
        $rows = self::readSheet($spreadsheetId, $range);

        if (empty($rows)) {
            return [];
        }

        $headers = $rows[0];
        $dataRows = array_slice($rows, 1);

        $getValue = static function ($data, $possibleKeys) {
            foreach ($possibleKeys as $key) {
                if (isset($data[$key]) && !empty($data[$key])) {
                    return trim($data[$key]);
                }
            }

            return null;
        };

        $offeringKey = static function (?string $courseCode, string $academicYear, string $semester): ?string {
            $code = $courseCode !== null ? trim($courseCode) : '';
            if ($code === '') {
                return null;
            }

            return strtolower($code)."\x1F".$academicYear."\x1F".$semester;
        };

        /** @var array<string, array<string, true>> */
        $sectionsPerOffering = [];

        foreach ($dataRows as $row) {
            $rowAssoc = array_combine(
                $headers,
                array_slice(array_pad($row, count($headers), null), 0, count($headers))
            );
            if (! is_array($rowAssoc)) {
                continue;
            }

            $courseCode = $getValue($rowAssoc, ['CourseCode', 'courseCode', 'Course Code']);
            $courseSection = $getValue($rowAssoc, ['CourseSection', 'courseSection', 'Course Section']);
            $academicYear = (string) ($getValue($rowAssoc, ['AcademicYear', 'academicYear', 'Academic Year']) ?? '');
            $semester = (string) ($getValue($rowAssoc, ['Semester', 'semester']) ?? '');
            $key = $offeringKey($courseCode, $academicYear, $semester);
            if ($key === null || $courseSection === null || $courseSection === '') {
                continue;
            }
            if (! isset($sectionsPerOffering[$key])) {
                $sectionsPerOffering[$key] = [];
            }
            $sectionsPerOffering[$key][trim($courseSection)] = true;
        }

        $courses = [];
        $seenCourses = []; // Track unique course combinations to avoid duplicates

        foreach ($dataRows as $row) {
            $rowAssoc = array_combine(
                $headers,
                array_slice(array_pad($row, count($headers), null), 0, count($headers))
            );
            if (! is_array($rowAssoc)) {
                continue;
            }

            // Get program coordinator email (check multiple possible column names)
            $programCoordinatorEmailValue = $getValue($rowAssoc, ['ProgramCoordinator', 'programCoordinator', 'Program Coordinator']);

            // Get course coordinator email to check if it's empty
            $courseCoordinatorValue = $getValue($rowAssoc, ['CourseCoordinator', 'courseCoordinator', 'Course Coordinator']);

            // Check if this row matches the program coordinator email AND has no course coordinator
            if (! $programCoordinatorEmailValue ||
                strtolower(trim($programCoordinatorEmailValue)) !== strtolower(trim($programCoordinatorEmail)) ||
                ! empty($courseCoordinatorValue)) {
                continue;
            }

            $courseCode = $getValue($rowAssoc, ['CourseCode', 'courseCode', 'Course Code']);
            $courseSection = $getValue($rowAssoc, ['CourseSection', 'courseSection', 'Course Section']);
            $courseName = $getValue($rowAssoc, ['CourseName', 'courseName', 'Course Name', 'CourseTitle', 'courseTitle', 'Course Title']);
            $academicYear = (string) ($getValue($rowAssoc, ['AcademicYear', 'academicYear', 'Academic Year']) ?? '');
            $semester = (string) ($getValue($rowAssoc, ['Semester', 'semester']) ?? '');

            $offKey = $offeringKey($courseCode, $academicYear, $semester);
            if ($offKey === null || $courseSection === null || $courseSection === '') {
                continue;
            }
            if (count($sectionsPerOffering[$offKey] ?? []) !== 1) {
                continue;
            }

            $uniqueKey = $courseCode.'|'.$courseSection;

            if ($courseCode && $courseSection && ! isset($seenCourses[$uniqueKey])) {
                $seenCourses[$uniqueKey] = true;
                $instructorEmail = $getValue($rowAssoc, ['InstructorEmail', 'instructorEmail', 'Instructor Email']);
                $firebaseCourse = null;
                if ($instructorEmail) {
                    $firebaseCourse = self::getCourseEvaluationFromFirestoreForInstructor(
                        $instructorEmail,
                        $courseCode,
                        $courseSection,
                        $academicYear ?: null,
                        $semester ?: null
                    );
                }

                if ($firebaseCourse !== null) {
                    $courses[] = self::enrichCoordinatorCourseListItemFromFirestore(
                        $firebaseCourse,
                        $courseCode,
                        $courseSection,
                        $courseName,
                        $instructorEmail
                    );
                } else {
                    $courses[] = [
                        'courseCode' => $courseCode,
                        'courseId' => $courseCode, // Alias for courseCode
                        'courseSection' => $courseSection,
                        'courseTitle' => $courseName ?? '',
                        'instructorEmail' => $instructorEmail ?? '',
                    ];
                }
            }
        }

        return $courses;
    }

    /**
     * Rows as associative arrays, optionally filtered by Semester column.
     *
     * @return list<array<string, string|null>>
     */
    public static function getRowsAssocForSemester(string $spreadsheetId, string $range, ?string $semester): array
    {
        $rows = self::readSheet($spreadsheetId, $range);
        if (empty($rows)) {
            return [];
        }

        $headers = $rows[0];
        $dataRows = array_slice($rows, 1);
        $out = [];

        foreach ($dataRows as $row) {
            $rowAssoc = array_combine(
                $headers,
                array_slice(array_pad($row, count($headers), null), 0, count($headers))
            );
            if (!is_array($rowAssoc)) {
                continue;
            }

            if ($semester !== null && $semester !== '') {
                $rowSem = $rowAssoc['Semester'] ?? $rowAssoc['semester'] ?? null;
                if ($rowSem === null || trim((string) $rowSem) !== trim((string) $semester)) {
                    continue;
                }
            }

            $out[] = $rowAssoc;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function sheetCellGet(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) {
                return trim((string) $row[$key]);
            }
        }

        return null;
    }

    /**
     * Normalize emails for comparison.
     */
    private static function emailsEqual(?string $a, ?string $b): bool
    {
        if ($a === null || $b === null || $a === '' || $b === '') {
            return false;
        }

        return strtolower(trim($a)) === strtolower(trim($b));
    }

    /**
     * Build email (lowercase) => display name from instructor rows.
     *
     * @param list<array<string, mixed>> $rows
     * @return array<string, string>
     */
    private static function buildInstructorEmailToNameMap(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $email = self::sheetCellGet($row, ['InstructorEmail', 'instructorEmail', 'Instructor Email']);
            $name = self::sheetCellGet($row, ['InstructorName', 'instructorName', 'Instructor Name']);
            if ($email && $name) {
                $map[strtolower($email)] = $name;
            }
        }

        return $map;
    }

    private static function nameFromEmailFallback(string $email): string
    {
        $local = strstr($email, '@', true) ?: $email;
        $local = str_replace(['.', '_', '-'], ' ', $local);

        return ucwords(strtolower($local));
    }

    /**
     * Resolve a person's display name using instructor rows, else from email local-part.
     *
     * @param array<string, string> $emailToName lowercase email => name
     */
    private static function resolvePersonName(string $email, array $emailToName): string
    {
        $key = strtolower(trim($email));
        if (isset($emailToName[$key])) {
            return $emailToName[$key];
        }

        return self::nameFromEmailFallback($email);
    }

    /**
     * @return list<array{email: string, name: string}>
     */
    private static function uniquePeopleList(array $people): array
    {
        $byEmail = [];
        foreach ($people as $p) {
            if (empty($p['email'])) {
                continue;
            }
            $k = strtolower(trim($p['email']));
            $byEmail[$k] = ['email' => trim($p['email']), 'name' => $p['name'] ?? ''];
        }

        return array_values($byEmail);
    }

    /**
     * Filter by single search string against email OR name (case-insensitive, partial).
     *
     * @param list<array{email: string, name: string}> $people
     * @return list<array{email: string, name: string}>
     */
    private static function applySingleSearchFilter(array $people, ?string $search): array
    {
        if ($search === null || trim($search) === '') {
            return $people;
        }

        $q = strtolower(trim($search));

        return array_values(array_filter($people, function ($p) use ($q) {
            return str_contains(strtolower($p['email']), $q)
                || str_contains(strtolower($p['name']), $q);
        }));
    }

    /**
     * @param list<array{email: string, name: string}> $people
     * @return list<array{email: string, name: string}>
     */
    private static function sortPeopleByLastName(array $people): array
    {
        usort($people, function ($a, $b) {
            $aLast = self::extractLastName($a['name'] ?? '');
            $bLast = self::extractLastName($b['name'] ?? '');

            if ($aLast === $bLast) {
                $aFull = strtolower(trim($a['name'] ?? ''));
                $bFull = strtolower(trim($b['name'] ?? ''));
                if ($aFull === $bFull) {
                    return strtolower(trim($a['email'] ?? '')) <=> strtolower(trim($b['email'] ?? ''));
                }

                return $aFull <=> $bFull;
            }

            return $aLast <=> $bLast;
        });

        return $people;
    }

    private static function extractLastName(string $name): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($name)) ?? '';
        if ($normalized === '') {
            return '';
        }

        $parts = explode(' ', $normalized);
        return strtolower(end($parts) ?: '');
    }

    /**
     * Courses for an instructor from the monitoring sheet (role=lecturer).
     * Shape matches SQL vinSections rows returned by LecturerCourseController.
     *
     * @return list<object{SectionID: string, CourseID: string, CourseCode: string, Session: string, StaffFName1: string, StaffLName1: string, CourseTitle: string}>
     */
    public static function getLecturerCoursesForInstructor(
        string $spreadsheetId,
        string $range,
        string $semester,
        string $instructorEmail,
        ?string $staffFName = null,
        ?string $staffLName = null
    ): array {
        $rows = self::getRowsAssocForSemester($spreadsheetId, $range, $semester);
        $courses = [];
        $seen = [];

        foreach ($rows as $row) {
            $inst = self::sheetCellGet($row, ['InstructorEmail', 'instructorEmail', 'Instructor Email']);
            if (! self::emailsEqual($inst, $instructorEmail)) {
                continue;
            }

            $courseCode = self::sheetCellGet($row, [
                'CourseCode',
                'courseCode',
                'Course Code',
            ]);
            $courseSection = self::sheetCellGet($row, [
                'CourseSection',
                'courseSection',
                'Course Section',
                'SectionID',
                'sectionId',
                'Section Id',
                'Section',
                'section',
            ]);
            if ($courseCode === null || $courseSection === null || $courseCode === '' || $courseSection === '') {
                continue;
            }

            $courseId = self::sheetCellGet($row, [
                'CourseID',
                'courseId',
                'Course Id',
            ]) ?? $courseCode;

            $key = strtolower($courseCode) . '|' . strtolower($courseSection);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $courseTitle = self::sheetCellGet($row, [
                'CourseName',
                'courseName',
                'Course Name',
                'CourseTitle',
                'courseTitle',
                'Course Title',
            ]) ?? '';

            $courses[] = (object) [
                'SectionID' => $courseSection,
                'CourseID' => $courseId,
                'CourseCode' => $courseCode,
                'Session' => $semester,
                'StaffFName1' => $staffFName ?? '',
                'StaffLName1' => $staffLName ?? '',
                'CourseTitle' => $courseTitle,
            ];
        }

        return $courses;
    }

    /**
     * Course code + section pairs from the sheet that the viewer may see for the given person,
     * using the same row rules as {@see getLecturersByReportingRole}.
     *
     * @param  string  $actingRole  lecturer | courseCoordinator | programCoordinator | chair | dean | VP
     * @return list<array{courseCode: string, courseSection: string}>
     */
    public static function getMonitoringCourseKeysForPerson(
        string $spreadsheetId,
        string $range,
        string $semester,
        string $actingRole,
        string $actorEmail,
        string $personEmail
    ): array {
        $rows = self::getRowsAssocForSemester($spreadsheetId, $range, $semester);
        $byKey = [];

        $addKey = function (?string $code, ?string $section) use (&$byKey) {
            if ($code === null || $section === null) {
                return;
            }
            $code = trim((string) $code);
            $section = trim((string) $section);
            if ($code === '' || $section === '') {
                return;
            }
            $k = strtolower($code) . '|' . strtolower($section);
            if (! isset($byKey[$k])) {
                $byKey[$k] = ['courseCode' => $code, 'courseSection' => $section];
            }
        };

        $personNorm = strtolower(trim($personEmail));

        foreach ($rows as $row) {
            // Accept both legacy and SQL-like column names for robust matching.
            $courseCode = self::sheetCellGet($row, [
                'CourseCode',
                'courseCode',
                'Course Code',
                'CourseID',
                'courseId',
                'Course Id',
            ]);
            $courseSection = self::sheetCellGet($row, [
                'CourseSection',
                'courseSection',
                'Course Section',
                'SectionID',
                'sectionId',
                'Section Id',
                'Section',
                'section',
            ]);

            switch ($actingRole) {
                case 'lecturer':
                    $inst = self::sheetCellGet($row, ['InstructorEmail', 'instructorEmail', 'Instructor Email']);
                    if (self::emailsEqual($inst, $personEmail)) {
                        $addKey($courseCode, $courseSection);
                    }
                    break;

                case 'courseCoordinator':
                    $cc = self::sheetCellGet($row, ['CourseCoordinator', 'courseCoordinator', 'Course Coordinator']);
                    $inst = self::sheetCellGet($row, ['InstructorEmail', 'instructorEmail', 'Instructor Email']);
                    if (self::emailsEqual($cc, $actorEmail) && $inst !== null && strtolower(trim($inst)) === $personNorm) {
                        $addKey($courseCode, $courseSection);
                    }
                    break;

                case 'programCoordinator':
                    $pc = self::sheetCellGet($row, ['ProgramCoordinator', 'programCoordinator', 'Program Coordinator']);
                    if (! self::emailsEqual($pc, $actorEmail)) {
                        break;
                    }
                    $ccVal = self::sheetCellGet($row, ['CourseCoordinator', 'courseCoordinator', 'Course Coordinator']);
                    $inst = self::sheetCellGet($row, ['InstructorEmail', 'instructorEmail', 'Instructor Email']);
                    $ccEmpty = $ccVal === null || $ccVal === '';
                    if ($ccEmpty) {
                        if ($inst !== null && strtolower(trim($inst)) === $personNorm) {
                            $addKey($courseCode, $courseSection);
                        }
                    } elseif (self::emailsEqual($ccVal, $personEmail)) {
                        $addKey($courseCode, $courseSection);
                    }
                    break;

                case 'chair':
                    $chair1 = self::sheetCellGet($row, ['Chair']);
                    $chair2 = self::sheetCellGet($row, ['Chair 2', 'Chair2', 'chair2']);
                    if (! self::emailsEqual($chair1, $actorEmail) && ! self::emailsEqual($chair2, $actorEmail)) {
                        break;
                    }
                    $progCoord = self::sheetCellGet($row, ['ProgramCoordinator', 'programCoordinator', 'Program Coordinator']);
                    if (self::emailsEqual($progCoord, $personEmail)) {
                        $addKey($courseCode, $courseSection);
                    }
                    break;

                case 'dean':
                    $dean = self::sheetCellGet($row, ['Dean']);
                    if (! self::emailsEqual($dean, $actorEmail)) {
                        break;
                    }
                    foreach (['Chair', 'Chair 2', 'Chair2'] as $chairKey) {
                        $chairEmail = self::sheetCellGet($row, [$chairKey]);
                        if (self::emailsEqual($chairEmail, $personEmail)) {
                            $addKey($courseCode, $courseSection);
                            break;
                        }
                    }
                    break;

                case 'VP':
                    $inst = self::sheetCellGet($row, ['InstructorEmail', 'instructorEmail', 'Instructor Email']);
                    if ($inst !== null && strtolower(trim($inst)) === $personNorm) {
                        $addKey($courseCode, $courseSection);
                    }
                    break;
            }
        }

        return array_values($byKey);
    }

    /**
     * Lecturers / coordinators in scope for the acting user's role (spreadsheet hierarchy).
     *
     * Roles: courseCoordinator, programCoordinator, chair, dean, VP
     *
     * @return list<array{email: string, name: string}>
     */
    public static function getLecturersByReportingRole(
        string $spreadsheetId,
        string $range,
        string $semester,
        string $actingRole,
        string $actorEmail,
        ?string $search = null
    ): array {
        $rows = self::getRowsAssocForSemester($spreadsheetId, $range, $semester);
        $emailToName = self::buildInstructorEmailToNameMap($rows);

        $push = function (array &$bucket, ?string $email, ?string $name) {
            if (!$email) {
                return;
            }
            $bucket[] = [
                'email' => trim($email),
                'name' => $name !== null && $name !== '' ? trim($name) : '',
            ];
        };

        $candidates = [];

        switch ($actingRole) {
            case 'courseCoordinator':
                foreach ($rows as $row) {
                    $cc = self::sheetCellGet($row, ['CourseCoordinator', 'courseCoordinator', 'Course Coordinator']);
                    if (!self::emailsEqual($cc, $actorEmail)) {
                        continue;
                    }
                    $instEmail = self::sheetCellGet($row, ['InstructorEmail', 'instructorEmail', 'Instructor Email']);
                    $instName = self::sheetCellGet($row, ['InstructorName', 'instructorName', 'Instructor Name']);
                    if ($instEmail) {
                        $push($candidates, $instEmail, $instName ?: self::resolvePersonName($instEmail, $emailToName));
                    }
                }
                break;

            case 'programCoordinator':
                foreach ($rows as $row) {
                    $pc = self::sheetCellGet($row, ['ProgramCoordinator', 'programCoordinator', 'Program Coordinator']);
                    if (!self::emailsEqual($pc, $actorEmail)) {
                        continue;
                    }
                    $ccVal = self::sheetCellGet($row, ['CourseCoordinator', 'courseCoordinator', 'Course Coordinator']);
                    if ($ccVal !== null && $ccVal !== '') {
                        $push($candidates, $ccVal, self::resolvePersonName($ccVal, $emailToName));
                    } else {
                        $instEmail = self::sheetCellGet($row, ['InstructorEmail', 'instructorEmail', 'Instructor Email']);
                        $instName = self::sheetCellGet($row, ['InstructorName', 'instructorName', 'Instructor Name']);
                        if ($instEmail) {
                            $push($candidates, $instEmail, $instName ?: self::resolvePersonName($instEmail, $emailToName));
                        }
                    }
                }
                break;

            case 'chair':
                foreach ($rows as $row) {
                    $chair1 = self::sheetCellGet($row, ['Chair']);
                    $chair2 = self::sheetCellGet($row, ['Chair 2', 'Chair2', 'chair2']);
                    if (!self::emailsEqual($chair1, $actorEmail) && !self::emailsEqual($chair2, $actorEmail)) {
                        continue;
                    }
                    $progCoord = self::sheetCellGet($row, ['ProgramCoordinator', 'programCoordinator', 'Program Coordinator']);
                    if ($progCoord) {
                        $push($candidates, $progCoord, self::resolvePersonName($progCoord, $emailToName));
                    }
                }
                break;

            case 'dean':
                foreach ($rows as $row) {
                    $dean = self::sheetCellGet($row, ['Dean']);
                    if (!self::emailsEqual($dean, $actorEmail)) {
                        continue;
                    }
                    foreach (['Chair', 'Chair 2', 'Chair2'] as $chairKey) {
                        $chairEmail = self::sheetCellGet($row, [$chairKey]);
                        if ($chairEmail) {
                            $push($candidates, $chairEmail, self::resolvePersonName($chairEmail, $emailToName));
                        }
                    }
                }
                break;

            case 'VP':
                foreach ($rows as $row) {
                    $instEmail = self::sheetCellGet($row, ['InstructorEmail', 'instructorEmail', 'Instructor Email']);
                    if ($instEmail) {
                        $instName = self::sheetCellGet($row, ['InstructorName', 'instructorName', 'Instructor Name']);
                        $push($candidates, $instEmail, $instName ?: self::resolvePersonName($instEmail, $emailToName));
                    }
                }
                break;

            default:
                return [];
        }

        $unique = self::uniquePeopleList($candidates);
        $withoutActor = array_values(array_filter($unique, function ($person) use ($actorEmail) {
            return !self::emailsEqual($person['email'] ?? null, $actorEmail);
        }));
        $filtered = self::applySingleSearchFilter($withoutActor, $search);

        return self::sortPeopleByLastName($filtered);
    }
}
