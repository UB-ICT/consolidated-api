<?php

namespace Modules\Xenegrade\Http\Controllers;

use App\Services\FirestoreService;
use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Xenegrade\Services\CourseMonitoringFormAccessService;

class LecturerCourseController extends Controller
{
    public function getLecturerCourses(Request $request, string $email, string $semester)
    {
        $validator = Validator::make(
            array_merge($request->query(), ['email' => $email, 'semester' => $semester]),
            [
                'email' => 'required|email',
                'semester' => 'required|string',
                'viewerEmail' => 'nullable|email',
                'role' => 'nullable|string',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $roleParamEarly = strtolower(trim((string) $request->query('role', '')));
        $lecturerRoleRequested = $roleParamEarly === 'lecturer';

        // 1. Find lecturer in staff table (SQL Server)
        $lecturer = DB::connection('sqlsrv')
            ->table('staff')
            ->where('staEmail', $email)
            //#->where('staType', 'Instructor')
            ->first();

        if (! $lecturer && ! $lecturerRoleRequested) {
            return response()->json(['error' => 'Lecturer not found'], 405);
        }

        if (! $lecturer) {
            $lecturer = (object) [
                'staEmail' => $email,
                'staFName' => '',
                'staLName' => '',
            ];
        }

        // 2. Get courses from vinSections and COURSE tables (SQL Server), when staff record exists
        $sqlCourses = collect();
        if ($lecturer->staFName !== '' || $lecturer->staLName !== '') {
            $sqlCourses = DB::connection('sqlsrv')
                ->table('vinSections as vs')
                ->join('COURSE as c', 'vs.CourseID', '=', 'c.couCourseID')
                ->where('vs.Session', $semester)
                ->where('vs.StaffFName1', $lecturer->staFName)
                ->where('vs.StaffLName1', $lecturer->staLName)
                ->where('vs.CourseStatus', 'Offered')
                ->select(
                    'vs.SectionID',
                    'vs.CourseID',
                    'vs.CourseCode',
                    'vs.Session',
                    'vs.StaffFName1',
                    'vs.StaffLName1',
                    'c.couTitle as CourseTitle'
                )
                ->get();
        }

        $courses = $sqlCourses;

        $spreadsheetId = env('GOOGLE_SHEET_ID');
        $range = env('COURSES_GOOGLE_SHEET_RANGE', 'courses!A1:Z2000');

        if ($spreadsheetId) {
            $viewerEmail = trim((string) $request->query('viewerEmail', $email));
            if ($viewerEmail === '') {
                $viewerEmail = $email;
            }

            $personEmail = trim($email);
            $samePerson = strtolower($viewerEmail) === strtolower($personEmail);
            $roleParam = strtolower(trim((string) $request->query('role', '')));
            $roleMap = [
                'lecturer' => 'lecturer',
                'coursecoordinator' => 'courseCoordinator',
                'programcoordinator' => 'programCoordinator',
                'chair' => 'chair',
                'dean' => 'dean',
                'vp' => 'VP',
            ];
            $requestedRole = $roleMap[$roleParam] ?? null;

            if ($requestedRole === 'lecturer') {
                if (! $samePerson) {
                    return response()->json([
                        'success' => false,
                        'message' => 'role=lecturer requires viewerEmail to match the lecturer email.',
                    ], 422);
                }
                $actingRoles = ['lecturer'];
            } elseif ($samePerson) {
                $actingRoles = ['lecturer'];
            } elseif ($requestedRole === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'When viewerEmail differs from the lecturer email, a valid role query is required (lecturer, courseCoordinator, programCoordinator, chair, dean, or VP).',
                ], 422);
            } else {
                $viewerSheetRoles = GoogleSheetService::checkEmailRoles($spreadsheetId, $range, $viewerEmail);
                $roleClaims = [
                    'courseCoordinator' => 'courseCoordinator',
                    'programCoordinator' => 'programCoordinator',
                    'chair' => 'chair',
                    'dean' => 'dean',
                    'VP' => 'VP',
                ];

                // Use union of all viewer roles for teacher-course visibility.
                // This ensures courseCoordinator visibility is included even when the active view role is broader.
                $actingRoles = [$requestedRole];
                foreach ($roleClaims as $roleName => $claimKey) {
                    if (!empty($viewerSheetRoles[$claimKey])) {
                        $actingRoles[] = $roleName;
                    }
                }
                $actingRoles = array_values(array_unique($actingRoles));
            }

            $lecturerOnly = $actingRoles === ['lecturer'];

            if ($lecturerOnly) {
                // Sheet is source of truth for instructor assignments (SQL may be empty for VP / sheet-only rows).
                $courses = collect(GoogleSheetService::getLecturerCoursesForInstructor(
                    $spreadsheetId,
                    $range,
                    $semester,
                    $personEmail,
                    $lecturer->staFName ?? null,
                    $lecturer->staLName ?? null
                ));

                if ($sqlCourses->isNotEmpty()) {
                    $sqlByKey = [];
                    foreach ($sqlCourses as $sqlCourse) {
                        $code = strtolower(trim((string) ($sqlCourse->CourseCode ?? '')));
                        $courseId = strtolower(trim((string) ($sqlCourse->CourseID ?? '')));
                        $sec = strtolower(trim((string) ($sqlCourse->SectionID ?? '')));
                        if ($sec === '') {
                            continue;
                        }
                        if ($code !== '') {
                            $sqlByKey[$code . '|' . $sec] = $sqlCourse;
                        }
                        if ($courseId !== '') {
                            $sqlByKey[$courseId . '|' . $sec] = $sqlCourse;
                        }
                    }

                    $courses = $courses->map(function ($course) use ($sqlByKey) {
                        $code = strtolower(trim((string) ($course->CourseCode ?? '')));
                        $courseId = strtolower(trim((string) ($course->CourseID ?? '')));
                        $sec = strtolower(trim((string) ($course->SectionID ?? '')));
                        $sql = $sqlByKey[$code . '|' . $sec] ?? $sqlByKey[$courseId . '|' . $sec] ?? null;
                        if ($sql && ! empty($sql->CourseTitle)) {
                            $course->CourseTitle = $sql->CourseTitle;
                        }

                        return $course;
                    })->values();
                }
            } else {
                $sheetKeys = [];
                foreach ($actingRoles as $actingRole) {
                    $keysForRole = GoogleSheetService::getMonitoringCourseKeysForPerson(
                        $spreadsheetId,
                        $range,
                        $semester,
                        $actingRole,
                        $viewerEmail,
                        $personEmail
                    );
                    foreach ($keysForRole as $pair) {
                        $sheetKeys[] = $pair;
                    }
                }

                $allowed = [];
                foreach ($sheetKeys as $pair) {
                    $code = strtolower(trim((string) ($pair['courseCode'] ?? '')));
                    $sec = strtolower(trim((string) ($pair['courseSection'] ?? '')));
                    if ($code !== '' && $sec !== '') {
                        $allowed[$code . '|' . $sec] = true;
                    }
                }

                $courses = $sqlCourses->filter(function ($course) use ($allowed) {
                    $code = strtolower(trim((string) ($course->CourseCode ?? '')));
                    $courseId = strtolower(trim((string) ($course->CourseID ?? '')));
                    $sec = strtolower(trim((string) ($course->SectionID ?? '')));

                    if ($sec === '') {
                        return false;
                    }

                    $matchesCode = $code !== '' && isset($allowed[$code . '|' . $sec]);
                    $matchesId = $courseId !== '' && isset($allowed[$courseId . '|' . $sec]);

                    return $matchesCode || $matchesId;
                })->values();
            }
        }

        return response()->json([
            'lecturer' => [
                'email' => $lecturer->staEmail,
            ],
            'semester' => $semester,
            'courses' => $courses,
        ]);
    }

    public function getLecturers(Request $request, string $email, string $semester)
    {
        $validator = Validator::make(
            array_merge($request->query(), ['email' => $email, 'semester' => $semester]),
            [
                'email' => 'required|email',
                'semester' => 'required|string',
                'role' => 'required|string',
                'search' => 'nullable|string|max:255',
                'q' => 'nullable|string|max:255',
                'limit' => 'nullable|integer|min:1|max:200',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $roleParam = strtolower(trim((string) $request->query('role')));
        
        $roleMap = [
            'coursecoordinator' => 'courseCoordinator',
            'programcoordinator' => 'programCoordinator',
            'chair' => 'chair',
            'dean' => 'dean',
            'vp' => 'VP',
        ];

        $actingRole = $roleMap[$roleParam] ?? null;
        if ($actingRole === null) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid role. Use courseCoordinator, programCoordinator, chair, dean, or VP.',
            ], 422);
        }

        $spreadsheetId = env('GOOGLE_SHEET_ID');
        $range = env('COURSES_GOOGLE_SHEET_RANGE', 'courses!A1:Z2000');

        if (!$spreadsheetId) {
            Log::error('Google Sheet ID not configured for getLecturers', ['email' => $email]);

            return response()->json([
                'success' => false,
                'message' => 'Google Sheet ID not configured.',
            ], 500);
        }

        $roles = GoogleSheetService::checkEmailRoles($spreadsheetId, $range, $email);
        $roleClaimKey = $actingRole === 'VP' ? 'VP' : $actingRole;
        if (empty($roles[$roleClaimKey])) {
            return response()->json([
                'success' => false,
                'message' => 'The given email does not have the requested role in the spreadsheet.',
            ], 403);
        }

        $search = trim((string) ($request->query('search') ?? ''));
        if ($search === '') {
            $search = trim((string) ($request->query('q') ?? ''));
        }
        $search = $search === '' ? null : $search;
        $limit = $request->query('limit');
        $limit = is_numeric($limit) ? (int) $limit : null;

        // Use union of all monitoring roles assigned to the viewer so that
        // courseCoordinator scope is still visible even when the UI is opened
        // under a broader role (programCoordinator/chair/dean/VP).
        $roleClaims = [
            'courseCoordinator' => 'courseCoordinator',
            'programCoordinator' => 'programCoordinator',
            'chair' => 'chair',
            'dean' => 'dean',
            'VP' => 'VP',
        ];
        $actingRoles = [$actingRole];
        foreach ($roleClaims as $roleName => $claimKey) {
            if (!empty($roles[$claimKey])) {
                $actingRoles[] = $roleName;
            }
        }
        $actingRoles = array_values(array_unique($actingRoles));

        $peopleBuckets = [];
        foreach ($actingRoles as $roleForQuery) {
            $bucket = GoogleSheetService::getLecturersByReportingRole(
                $spreadsheetId,
                $range,
                $semester,
                $roleForQuery,
                $email,
                $search
            );
            foreach ($bucket as $p) {
                $peopleBuckets[] = $p;
            }
        }

        // Deduplicate by email after combining all role scopes.
        $peopleMap = [];
        foreach ($peopleBuckets as $p) {
            $e = strtolower(trim((string) ($p['email'] ?? '')));
            if ($e === '') {
                continue;
            }
            $peopleMap[$e] = [
                'email' => trim((string) ($p['email'] ?? '')),
                'name' => trim((string) ($p['name'] ?? '')),
            ];
        }
        $people = array_values($peopleMap);

        // Final safeguard: return people alphabetically by display name.
        usort($people, function ($a, $b) {
            $aName = strtolower(trim((string) ($a['name'] ?? '')));
            $bName = strtolower(trim((string) ($b['name'] ?? '')));

            if ($aName === $bName) {
                return strtolower(trim((string) ($a['email'] ?? ''))) <=> strtolower(trim((string) ($b['email'] ?? '')));
            }

            return $aName <=> $bName;
        });

        // Allow caller to request top N items (used for initial default load).
        if ($limit !== null && $limit > 0) {
            $people = array_slice($people, 0, $limit);
        }

        return response()->json([
            'success' => true,
            'data' => $people,
            'semester' => $semester,
            'role' => $actingRole,
            'search' => $search,
        ]);
    }

    public function getCourseMonitoringMenu(Request $request, string $email, string $semester)
    {
        $validator = Validator::make(
            array_merge($request->query(), ['email' => $email, 'semester' => $semester]),
            [
                'email' => 'required|email',
                'semester' => 'required|string',
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

        if (!$spreadsheetId) {
            Log::error('Google Sheet ID not configured for getCourseMonitoringMenu', ['email' => $email]);

            return response()->json([
                'success' => false,
                'message' => 'Google Sheet ID not configured.',
            ], 500);
        }

        $roles = GoogleSheetService::checkEmailRoles($spreadsheetId, $range, $email);
        $settingsDoc = FirestoreService::getDocument(
            CourseMonitoringFormAccessService::COLLECTION,
            CourseMonitoringFormAccessService::DOCUMENT_ID
        );
        $formAccess = CourseMonitoringFormAccessService::flagsFromDocument(is_array($settingsDoc) ? $settingsDoc : null);
        $level = $this->resolveCourseMonitoringMenuLevel($roles);
        $menu = $this->buildCourseMonitoringMenuItems($level, $formAccess);

        return response()->json([
            'success' => true,
            'data' => [
                'courseMonitoringMenu' => $menu,
                'formAccess' => $formAccess,
            ],
        ], 200);
    }

    /**
     * Highest course-monitoring tier from spreadsheet roles (cumulative menu through this tier).
     * VP has no dedicated tab; they receive the same cumulative menu as dean (tiers 1–5).
     */
    private function resolveCourseMonitoringMenuLevel(array $roles): int
    {
        if (!empty($roles['VP'])) {
            return 5;
        }
        if (!empty($roles['dean'])) {
            return 5;
        }
        if (!empty($roles['chair'])) {
            return 4;
        }
        if (!empty($roles['programCoordinator'])) {
            return 3;
        }
        if (!empty($roles['courseCoordinator'])) {
            return 2;
        }
        if (!empty($roles['lecturer'])) {
            return 1;
        }

        return 0;
    }

    /**
     * @param  array{
     *     enableCourseMonitoringForm: bool,
     *     enableCourseCoordinatorForm: bool,
     *     enableProgramCoordinatorForm: bool,
     *     enableAnnualChairForm: bool,
     *     enableAnnualDeanForm: bool,
     *     enableAnnualVpForm: bool
     * }  $formAccess
     * @return list<array{path: string, label: string, role: string}>
     */
    private function buildCourseMonitoringMenuItems(int $level, array $formAccess): array
    {
        $tiers = [
            1 => [
                'path' => '/course-monitoring',
                'label' => 'Course monitoring',
                'role' => 'lecturer',
                'flag' => 'enableCourseMonitoringForm',
            ],
            2 => [
                'path' => '/course-monitoring/course-coordinator',
                'label' => 'Course coordinator',
                'role' => 'courseCoordinator',
                'flag' => 'enableCourseCoordinatorForm',
            ],
            3 => [
                'path' => '/course-monitoring/program-coordinator',
                'label' => 'Program coordinator',
                'role' => 'programCoordinator',
                'flag' => 'enableProgramCoordinatorForm',
            ],
            4 => [
                'path' => '/course-monitoring/chair-coordinator',
                'label' => 'Chair',
                'role' => 'chair',
                'flag' => 'enableAnnualChairForm',
            ],
            5 => [
                'path' => '/course-monitoring/dean',
                'label' => 'Dean',
                'role' => 'dean',
                'flag' => 'enableAnnualDeanForm',
            ],
        ];

        $menu = [];
        for ($i = 1; $i <= $level; $i++) {
            $tier = $tiers[$i];
            $flagKey = $tier['flag'];
            if (empty($formAccess[$flagKey])) {
                continue;
            }
            $menu[] = [
                'path' => $tier['path'],
                'label' => $tier['label'],
                'role' => $tier['role'],
            ];
        }

        return $menu;
    }
}
