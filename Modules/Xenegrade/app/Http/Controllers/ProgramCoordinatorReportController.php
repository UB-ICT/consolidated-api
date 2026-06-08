<?php

namespace Modules\Xenegrade\Http\Controllers;

use App\Services\FirestoreService;
use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class ProgramCoordinatorReportController extends Controller
{
    protected string $collectionName = 'cmon_programCoordinatorReport';

    protected string $settingsCollectionName = 'cmon_courseMonitoringSettings';

    protected string $settingsDocumentId = 'global';

    public function getProgramCoordinatorSheetRows(string $email)
    {
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $spreadsheetId = env('GOOGLE_SHEET_ID');
        $range = env('PROGRAM_COORDINATORS_GOOGLE_SHEET_RANGE', 'program-coordinators!A1:Z2000');
        if (! $spreadsheetId) {
            return response()->json([
                'success' => false,
                'message' => 'Google Sheet ID not configured.',
            ], 500);
        }

        $rows = GoogleSheetService::readSheet($spreadsheetId, $range);
        if ($rows === []) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No rows found in the program-coordinators sheet.',
            ]);
        }

        $headers = $rows[0];
        $dataRows = array_slice($rows, 1);
        $out = [];
        $emailNorm = strtolower(trim($email));

        foreach ($dataRows as $row) {
            $rowAssoc = array_combine(
                $headers,
                array_slice(array_pad($row, count($headers), null), 0, count($headers))
            );
            if (! is_array($rowAssoc)) {
                continue;
            }

            $programCoordinator = $this->sheetCell($rowAssoc, [
                'programCoordinator'
            ]);
            if ($programCoordinator === null || strtolower(trim($programCoordinator)) !== $emailNorm) {
                continue;
            }
            $out[] = $rowAssoc;
        }

        return response()->json([
            'data' => $out
        ]);
    }

    public function getProgramReport(string $email, string $programCode, string $academicYear)
    {
        $doc = $this->getOrCreateOwnerDocument($email);
        $reports = $doc['reports'] ?? [];
        $idx = $this->findReportIndex($reports, $programCode, $academicYear);

        if ($idx === -1) {
            $settings = $this->loadSettings();
            if ($settings === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'cmon_courseMonitoringSettings/global was not found. Configure settings first.',
                ], 422);
            }
            $report = $this->buildDefaultReport($email, $programCode, $academicYear, $settings);
            $reports[] = $report;
            $this->saveOwnerReports($email, $reports);

            return response()->json([
                'success' => true,
                'data' => $report,
                'meta' => ['initialized' => true],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->stripDeprecatedEnrolmentFields($reports[$idx]),
            'meta' => ['initialized' => false],
        ]);
    }

    public function listProgramReports(string $email)
    {
        $doc = $this->getOrCreateOwnerDocument($email);
        $reports = $doc['reports'] ?? [];
        $reports = array_map(fn ($r) => is_array($r) ? $this->stripDeprecatedEnrolmentFields($r) : $r, $reports);
        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    public function upsertProgramReport(Request $request, string $email, string $programCode, string $academicYear)
    {
        $validator = Validator::make($request->all(), [
            'programDetails' => 'sometimes|array',
            'sectionA_programIdentification' => 'sometimes|array',
            'sectionB_statisticalInformation' => 'sometimes|array',
            'sectionC_programContext' => 'sometimes|array',
            'sectionD_courseInformationSummary' => 'sometimes|array',
            'sectionE_programManagementAdministration' => 'sometimes|array',
            'sectionF_summaryProgramEvaluation' => 'sometimes|array',
            'sectionG_programCourseEvaluation' => 'sometimes|array',
            'sectionH_independentOpinion' => 'sometimes|array',
            'sectionI_signature' => 'sometimes|array',
            'reviewNotes' => 'sometimes|array',
            'appendix' => 'sometimes|array',
            'hasCoordinatorSubmitted' => 'sometimes|boolean',
            'hasChairApproved' => 'sometimes|boolean',
            'hasQaReceived' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $doc = $this->getOrCreateOwnerDocument($email);
        $reports = $doc['reports'] ?? [];
        $idx = $this->findReportIndex($reports, $programCode, $academicYear);

        if ($idx === -1) {
            $settings = $this->loadSettings();
            if ($settings === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'cmon_courseMonitoringSettings/global was not found. Configure settings first.',
                ], 422);
            }
            $reports[] = $this->buildDefaultReport($email, $programCode, $academicYear, $settings);
            $idx = count($reports) - 1;
        }

        $existing = $reports[$idx];
        if (! empty($existing['hasCoordinatorSubmitted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Program coordinator report has already been submitted and is read-only.',
            ], 422);
        }
        $updateData = $request->only([
            'programDetails',
            'sectionA_programIdentification',
            'sectionB_statisticalInformation',
            'sectionC_programContext',
            'sectionD_courseInformationSummary',
            'sectionE_programManagementAdministration',
            'sectionF_summaryProgramEvaluation',
            'sectionG_programCourseEvaluation',
            'sectionH_independentOpinion',
            'sectionI_signature',
            'reviewNotes',
            'appendix',
            'hasCoordinatorSubmitted',
            'hasChairApproved',
            'hasQaReceived',
        ]);
        $updated = array_merge($existing, $updateData);
        $updated['programDetails'] = array_merge($existing['programDetails'] ?? [], $updateData['programDetails'] ?? []);
        $updated['programDetails']['dateOfReport'] = now()->toDateString();
        if (! empty($updated['hasCoordinatorSubmitted']) && empty($updated['programDetails']['submissionDate'])) {
            $updated['programDetails']['submissionDate'] = now()->toDateString();
        }
        $updated = $this->stripDeprecatedEnrolmentFields($updated);

        $reports[$idx] = $updated;
        $this->saveOwnerReports($email, $reports);

        return response()->json([
            'success' => true,
            'message' => 'Program coordinator report updated successfully',
            'data' => $updated,
        ]);
    }

    public function deleteProgramReport(string $email, string $programCode, string $academicYear)
    {
        $doc = $this->getOrCreateOwnerDocument($email);
        $reports = $doc['reports'] ?? [];
        $idx = $this->findReportIndex($reports, $programCode, $academicYear);

        if ($idx === -1) {
            return response()->json([
                'success' => false,
                'message' => 'Program report not found',
            ], 404);
        }

        array_splice($reports, $idx, 1);
        $this->saveOwnerReports($email, $reports);

        return response()->json([
            'success' => true,
            'message' => 'Program report deleted successfully',
        ]);
    }

    /**
     * Program rows from the program-coordinators sheet where Chair / Chair 2 matches the chair email.
     * Enriches with submission state for the requested academic year.
     */
    public function getChairProgramCoordinatorPrograms(Request $request, string $chairEmail)
    {
        $validator = Validator::make(
            array_merge($request->all(), ['chairEmail' => $chairEmail, 'academicYear' => $request->query('academicYear')]),
            [
                'chairEmail' => 'required|email',
                'academicYear' => 'required|string',
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $academicYear = trim((string) $request->query('academicYear'));
        $spreadsheetId = env('GOOGLE_SHEET_ID');
        $range = env('PROGRAM_COORDINATORS_GOOGLE_SHEET_RANGE', 'program-coordinators!A1:Z2000');
        if (! $spreadsheetId) {
            return response()->json([
                'success' => false,
                'message' => 'Google Sheet ID not configured.',
            ], 500);
        }

        $rows = GoogleSheetService::readSheet($spreadsheetId, $range);
        if ($rows === []) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $headers = $rows[0];
        $dataRows = array_slice($rows, 1);
        $chairNorm = strtolower(trim($chairEmail));
        $out = [];
        $seen = [];

        foreach ($dataRows as $row) {
            $rowAssoc = array_combine(
                $headers,
                array_slice(array_pad($row, count($headers), null), 0, count($headers))
            );
            if (! is_array($rowAssoc)) {
                continue;
            }
            if (! $this->rowChairMatches($rowAssoc, $chairNorm)) {
                continue;
            }
            $pcEmail = $this->programCoordinatorEmailFromRow($rowAssoc);
            $programCode = $this->programCodeFromRow($rowAssoc);
            if ($pcEmail === null || $programCode === null) {
                continue;
            }
            $programName = $this->programNameFromRow($rowAssoc) ?? '';
            $k = strtolower($pcEmail).'|'.strtolower($programCode);
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;

            $doc = FirestoreService::getDocument($this->collectionName, $pcEmail);
            $reports = is_array($doc) ? ($doc['reports'] ?? []) : [];
            $idx = $this->findReportIndex($reports, $programCode, $academicYear);
            $reportExists = $idx >= 0;
            $hasSubmitted = false;
            if ($reportExists && isset($reports[$idx]) && is_array($reports[$idx])) {
                $hasSubmitted = ! empty($reports[$idx]['hasCoordinatorSubmitted']);
            }

            $out[] = [
                'programCode' => $programCode,
                'programName' => $programName,
                'programCoordinatorEmail' => $pcEmail,
                'reportExists' => $reportExists,
                'hasCoordinatorSubmitted' => $hasSubmitted,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $out,
        ]);
    }

    /**
     * Fetch a program coordinator report for chair review without creating a default report.
     */
    public function getReportForChair(string $chairEmail, string $pcEmail, string $programCode, string $academicYear)
    {
        $validator = Validator::make([
            'chairEmail' => $chairEmail,
            'pcEmail' => $pcEmail,
            'programCode' => $programCode,
            'academicYear' => $academicYear,
        ], [
            'chairEmail' => 'required|email',
            'pcEmail' => 'required|email',
            'programCode' => 'required|string',
            'academicYear' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! $this->chairOwnsProgram($chairEmail, $pcEmail, $programCode)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned as chair for this program in the program-coordinators sheet.',
            ], 403);
        }

        $doc = FirestoreService::getDocument($this->collectionName, $pcEmail);
        if ($doc === null) {
            return response()->json([
                'success' => true,
                'data' => null,
                'meta' => ['exists' => false],
            ]);
        }
        $reports = $doc['reports'] ?? [];
        $idx = $this->findReportIndex($reports, $programCode, $academicYear);
        if ($idx === -1) {
            return response()->json([
                'success' => true,
                'data' => null,
                'meta' => ['exists' => false],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->stripDeprecatedEnrolmentFields($reports[$idx]),
            'meta' => ['exists' => true],
        ]);
    }

    /**
     * Chair updates reviewNotes (and optional hasChairApproved) on the program coordinator's report.
     */
    public function upsertChairReview(Request $request, string $chairEmail, string $pcEmail, string $programCode, string $academicYear)
    {
        $validator = Validator::make(
            array_merge($request->all(), [
                'chairEmail' => $chairEmail,
                'pcEmail' => $pcEmail,
                'programCode' => $programCode,
                'academicYear' => $academicYear,
            ]),
            [
                'chairEmail' => 'required|email',
                'pcEmail' => 'required|email',
                'programCode' => 'required|string',
                'academicYear' => 'required|string',
                'reviewNotes' => 'sometimes|array',
                'hasChairApproved' => 'sometimes|boolean',
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! $this->chairOwnsProgram($chairEmail, $pcEmail, $programCode)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned as chair for this program in the program-coordinators sheet.',
            ], 403);
        }

        $doc = FirestoreService::getDocument($this->collectionName, $pcEmail);
        $reports = is_array($doc) ? ($doc['reports'] ?? []) : [];
        $idx = $this->findReportIndex($reports, $programCode, $academicYear);
        if ($idx === -1) {
            return response()->json([
                'success' => false,
                'message' => 'Program report not found',
            ], 404);
        }

        $existing = $reports[$idx];
        if (empty($existing['hasCoordinatorSubmitted'])) {
            return response()->json([
                'success' => false,
                'message' => 'The program coordinator has not submitted this report yet.',
            ], 422);
        }

        $merged = $existing;
        if ($request->has('reviewNotes') && is_array($request->input('reviewNotes'))) {
            $prevNotes = is_array($existing['reviewNotes'] ?? null) ? $existing['reviewNotes'] : [];
            $merged['reviewNotes'] = array_merge($prevNotes, $request->input('reviewNotes'));
        }
        if ($request->has('hasChairApproved')) {
            $merged['hasChairApproved'] = (bool) $request->input('hasChairApproved');
        }
        $merged = $this->stripDeprecatedEnrolmentFields($merged);

        $reports[$idx] = $merged;
        $this->saveOwnerReports($pcEmail, $reports);

        return response()->json([
            'success' => true,
            'message' => 'Chair review saved',
            'data' => $merged,
        ]);
    }

    /**
     * @param  array<string, mixed>  $rowAssoc
     */
    private function rowChairMatches(array $rowAssoc, string $chairNorm): bool
    {
        $c1 = $this->sheetCell($rowAssoc, ['Chair', 'chair']);
        $c2 = $this->sheetCell($rowAssoc, ['Chair 2', 'Chair2', 'chair 2', 'Chair_2']);

        return ($c1 !== null && strtolower(trim($c1)) === $chairNorm)
            || ($c2 !== null && strtolower(trim($c2)) === $chairNorm);
    }

    /**
     * @param  array<string, mixed>  $rowAssoc
     */
    private function programCoordinatorEmailFromRow(array $rowAssoc): ?string
    {
        return $this->sheetCell($rowAssoc, ['programCoordinator', 'ProgramCoordinator', 'Program Coordinator']);
    }

    /**
     * @param  array<string, mixed>  $rowAssoc
     */
    private function programCodeFromRow(array $rowAssoc): ?string
    {
        $v = $this->sheetCell($rowAssoc, ['programCode', 'Program Code', 'ProgramCode']);

        return $v !== null ? trim($v) : null;
    }

    /**
     * @param  array<string, mixed>  $rowAssoc
     */
    private function programNameFromRow(array $rowAssoc): ?string
    {
        return $this->sheetCell($rowAssoc, ['programName', 'Program Name', 'program_name']);
    }

    private function chairOwnsProgram(string $chairEmail, string $pcEmail, string $programCode): bool
    {
        $spreadsheetId = env('GOOGLE_SHEET_ID');
        $range = env('PROGRAM_COORDINATORS_GOOGLE_SHEET_RANGE', 'program-coordinators!A1:Z2000');
        if (! $spreadsheetId) {
            return false;
        }
        $rows = GoogleSheetService::readSheet($spreadsheetId, $range);
        if ($rows === []) {
            return false;
        }
        $headers = $rows[0];
        $dataRows = array_slice($rows, 1);
        $chairNorm = strtolower(trim($chairEmail));
        $pcNorm = strtolower(trim($pcEmail));
        $codeNorm = strtolower(trim($programCode));

        foreach ($dataRows as $row) {
            $rowAssoc = array_combine(
                $headers,
                array_slice(array_pad($row, count($headers), null), 0, count($headers))
            );
            if (! is_array($rowAssoc)) {
                continue;
            }
            if (! $this->rowChairMatches($rowAssoc, $chairNorm)) {
                continue;
            }
            $rowPc = $this->programCoordinatorEmailFromRow($rowAssoc);
            $rowCode = $this->programCodeFromRow($rowAssoc);
            if ($rowPc === null || $rowCode === null) {
                continue;
            }
            if (strtolower(trim($rowPc)) === $pcNorm && strcasecmp(trim($rowCode), trim($programCode)) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function getOrCreateOwnerDocument(string $email): array
    {
        $doc = FirestoreService::getDocument($this->collectionName, $email);
        if ($doc !== null) {
            return $doc;
        }

        $payload = [
            'email' => $email,
            'reports' => [],
        ];
        $ref = FirestoreService::firestore()->collection($this->collectionName)->document($email);
        $ref->set($payload, ['merge' => true]);

        return $payload;
    }

    /**
     * @param  list<array<string, mixed>>  $reports
     */
    private function saveOwnerReports(string $email, array $reports): void
    {
        $ref = FirestoreService::firestore()->collection($this->collectionName)->document($email);
        $ref->set([
            'email' => $email,
            'reports' => $reports,
        ], ['merge' => true]);
    }

    /**
     * @param  list<array<string, mixed>>  $reports
     */
    private function findReportIndex(array $reports, string $programCode, string $academicYear): int
    {
        foreach ($reports as $index => $report) {
            $details = $report['programDetails'] ?? [];
            $candidateProgram = (string) ($details['programCode'] ?? '');
            $candidateYear = (string) ($details['academicYear'] ?? '');
            if (strcasecmp($candidateProgram, trim($programCode)) === 0 && strcasecmp($candidateYear, trim($academicYear)) === 0) {
                return $index;
            }
        }

        return -1;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadSettings(): ?array
    {
        $settings = FirestoreService::getDocument($this->settingsCollectionName, $this->settingsDocumentId);
        if (! is_array($settings)) {
            return null;
        }
        if (! isset($settings['defaultYearColumns']) || ! is_array($settings['defaultYearColumns']) || $settings['defaultYearColumns'] === []) {
            return null;
        }
        if (! isset($settings['defaultGroupACategories']) || ! is_array($settings['defaultGroupACategories']) || $settings['defaultGroupACategories'] === []) {
            return null;
        }
        if (! array_key_exists('currentYear', $settings) || ! array_key_exists('currentSemester', $settings)) {
            return null;
        }

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function buildDefaultReport(string $email, string $programCode, string $academicYear, array $settings): array
    {
        $yearColumns = $settings['defaultYearColumns'];
        $categories = $settings['defaultGroupACategories'];

        $valuesByYear = [];
        foreach ($yearColumns as $column) {
            $valuesByYear[(string) $column] = 0;
        }

        $groupACategories = [];
        foreach ($categories as $category) {
            $groupACategories[] = [
                'categoryName' => (string) $category,
                'valuesByYear' => $valuesByYear,
            ];
        }

        return [
            'reportId' => trim($programCode) . '-' . trim($academicYear),
            'hasCoordinatorSubmitted' => false,
            'hasChairApproved' => false,
            'hasQaReceived' => false,
            'programDetails' => [
                'department' => '',
                'departmentChair' => '',
                'programCoordinator' => '',
                'programCoordinatorEmail' => $email,
                'programCoordinatorName' => '',
                'programTitle' => '',
                'programCode' => trim($programCode),
                'academicYear' => trim($academicYear),
                'currentSemester' => (string) $settings['currentSemester'],
                'currentYear' => (string) $settings['currentYear'],
                'submissionDate' => null,
                'dateOfReport' => null,
            ],
            'sectionA_programIdentification' => [
                'department' => '',
                'departmentChair' => '',
                'programCoordinator' => '',
                'programTitle' => '',
                'programCode' => trim($programCode),
                'personCompletingName' => '',
                'personCompletingPosition' => '',
                'academicYearAppliesTo' => trim($academicYear),
            ],
            'sectionB_statisticalInformation' => [
                'undergraduate' => ['studentsStarted' => 0, 'studentsCompleted' => 0],
                'graduate' => ['studentsStarted' => 0, 'studentsCompleted' => 0],
                'apparentCompletionRate' => [
                    'undergraduatePercent' => 0,
                    'graduatePercent' => 0,
                    'specialFactorsComment' => '',
                ],
                'enrolmentManagement' => [
                    'roleDescription' => '',
                    'yearColumns' => $yearColumns,
                    'groupAPrograms' => [[
                        'label' => 'Group A',
                        'programName' => '',
                        'categories' => $groupACategories,
                        /** @deprecated Prefer comments; kept for backwards compatibility */
                        'notes' => '',
                        'comments' => '',
                    ]],
                ],
                'destinationOfGraduates' => [
                    'surveyDate' => null,
                    'numberSurveyed' => 0,
                    'numberResponded' => 0,
                    'responseRatePercent' => 0,
                    'table2' => [
                        'rows' => [[
                            'destination' => '',
                            'furtherStudy' => '',
                            'otherReasons' => '',
                            'employedInSubjectField' => '',
                            'otherEmployment' => '',
                            'unemployed' => '',
                        ]],
                    ],
                    'employmentDestinationsAnalysis' => '',
                    'strengths' => '',
                    'recommendations' => '',
                ],
            ],
            'sectionC_programContext' => [
                'significantInternalChanges' => '',
                'internalImplications' => '',
                'significantExternalChanges' => '',
                'externalImplications' => '',
            ],
            'sectionD_courseInformationSummary' => [
                'courseResults' => [
                    'howCourseReportsAreUsed' => '',
                    'completionRateAnalysis' => '',
                    'gradeDistributionAnalysis' => '',
                    'trendAnalysis' => '',
                ],
                'significantResultsOrVariations' => [[
                    'courseName' => '',
                    'significantResultOrVariation' => '',
                    'investigationUndertaken' => '',
                    'reasonForSignificantResultOrVariation' => '',
                    'actionTakenIfRequired' => '',
                ]],
                'plannedButNotTaught' => [[
                    'courseCode' => '',
                    'courseTitle' => '',
                    'modality' => '',
                    'explanation' => '',
                    'compensatingActionsIfRequired' => '',
                ]],
                'unitsOfWorkNotTaughtCompensatingActions' => [[
                    'courseCode' => '',
                    'courseTitle' => '',
                    'unitOfWork' => '',
                    'reason' => '',
                    'compensatingActionIfRequired' => '',
                ]],
            ],
            'sectionE_programManagementAdministration' => [
                'difficultiesEncountered' => '',
                'impactOnProgramObjectives' => '',
                'proposedAction' => '',
            ],
            'sectionF_summaryProgramEvaluation' => [
                'graduatingStudentsEvaluation' => [
                    'surveyDate' => null,
                    'recommendationsStrengthsSuggestions' => '',
                    'analysis' => '',
                    'proposedProgramChanges' => '',
                    'surveyReportPdf' => [
                        'fileName' => '',
                        'mimeType' => '',
                        'fileDataBase64' => '',
                        'uploadedAt' => null,
                    ],
                ],
            ],
            'sectionG_programCourseEvaluation' => [
                'coursesTaughtEvaluation' => [[
                    'courseCode' => '',
                    'courseTitle' => '',
                    'studentEvaluations' => '',
                    'otherEvaluation' => '',
                    'actionPlanned' => '',
                ]],
                'programCampusLocations' => [
                    'belmopanMainCampus' => false,
                    'belizeCity' => false,
                    'puntaGordaTown' => false,
                    'centralFarm' => false,
                ],
                'programCoursesOffered' => [[
                    'year' => '',
                    'courseCode' => '',
                    'courseTitle' => '',
                    'type' => '',
                    'creditHours' => 0,
                    'departmentOrLocation' => '',
                ]],
            ],
            'sectionH_independentOpinion' => [
                'mattersRaisedByEvaluator' => '',
                'programCoordinatorComment' => '',
                'implicationsForPlanning' => '',
                'attachmentPdf' => [
                    'fileName' => '',
                    'mimeType' => '',
                    'fileDataBase64' => '',
                    'uploadedAt' => null,
                ],
            ],
            'sectionI_signature' => [
                'programCoordinatorSignatureName' => '',
                'programCoordinatorSignedDate' => null,
                'chairReceiptSignatureName' => '',
                'chairReceiptSignedDate' => null,
            ],
            'reviewNotes' => [
                'sectionA_programIdentification' => '',
                'sectionB_statisticalInformation' => '',
                'sectionC_programContext' => '',
                'sectionD_courseInformationSummary' => '',
                'sectionE_programManagementAdministration' => '',
                'sectionF_summaryProgramEvaluation' => '',
                'sectionG_programCourseEvaluation' => '',
                'sectionH_independentOpinion' => '',
                'sectionI_signature' => '',
            ],
            'appendix' => [
                'documents' => [],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $keys
     */
    private function sheetCell(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== null && trim((string) $row[$key]) !== '') {
                return trim((string) $row[$key]);
            }
        }

        return null;
    }

    /**
     * Remove deprecated enrolment-management fields before persisting.
     *
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    private function stripDeprecatedEnrolmentFields(array $report): array
    {
        if (isset($report['sectionB_statisticalInformation']['enrolmentManagement']['groupAPrograms'])
            && is_array($report['sectionB_statisticalInformation']['enrolmentManagement']['groupAPrograms'])) {
            $report['sectionB_statisticalInformation']['enrolmentManagement']['groupAPrograms'] = array_map(
                function ($group) {
                    if (is_array($group)) {
                        unset($group['analysisByStartYear']);
                    }
                    return $group;
                },
                $report['sectionB_statisticalInformation']['enrolmentManagement']['groupAPrograms']
            );
        }

        if (isset($report['sectionB_statisticalInformation']['destinationOfGraduates']['table2'])
            && is_array($report['sectionB_statisticalInformation']['destinationOfGraduates']['table2'])) {
            $table2 = $report['sectionB_statisticalInformation']['destinationOfGraduates']['table2'];
            $legacyAvail = isset($table2['availableForEmployment']) && is_array($table2['availableForEmployment'])
                ? $table2['availableForEmployment']
                : [];
            $legacyNotAvail = isset($table2['notAvailableForEmployment']) && is_array($table2['notAvailableForEmployment'])
                ? $table2['notAvailableForEmployment']
                : [];
            $rows = isset($table2['rows']) && is_array($table2['rows']) ? $table2['rows'] : [];
            $normalizedRows = [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $normalizedRows[] = [
                    'destination' => (string) ($row['destination'] ?? ''),
                    'furtherStudy' => (string) ($row['furtherStudy'] ?? ''),
                    'otherReasons' => (string) ($row['otherReasons'] ?? ''),
                    'employedInSubjectField' => (string) ($row['employedInSubjectField'] ?? ''),
                    'otherEmployment' => (string) ($row['otherEmployment'] ?? ''),
                    'unemployed' => (string) ($row['unemployed'] ?? ''),
                ];
            }
            if ($normalizedRows === []) {
                $normalizedRows[] = [
                    'destination' => '',
                    'furtherStudy' => (string) ($legacyNotAvail['furtherStudy'] ?? $legacyAvail['furtherStudy'] ?? ''),
                    'otherReasons' => (string) ($legacyNotAvail['otherReasons'] ?? $legacyAvail['otherReasons'] ?? ''),
                    'employedInSubjectField' => (string) ($legacyAvail['employedInSubjectField'] ?? ''),
                    'otherEmployment' => (string) ($legacyAvail['otherEmployment'] ?? ''),
                    'unemployed' => (string) ($legacyAvail['unemployed'] ?? ''),
                ];
            }
            $table2['rows'] = $normalizedRows;
            unset($table2['availableForEmployment'], $table2['notAvailableForEmployment'], $table2['percentOfRespondents']);
            $report['sectionB_statisticalInformation']['destinationOfGraduates']['table2'] = $table2;
        }

        return $report;
    }
}
