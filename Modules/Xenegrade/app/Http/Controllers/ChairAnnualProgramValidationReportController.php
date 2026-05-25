<?php

namespace Modules\Xenegrade\Http\Controllers;

use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class ChairAnnualProgramValidationReportController extends Controller
{
    protected string $collectionName = 'cmon_ChairAnnualProgramValidationReport';

    public function listReports(string $chairEmail)
    {
        $doc = $this->getOrCreateOwnerDocument($chairEmail);

        return response()->json([
            'success' => true,
            'data' => $doc['reports'] ?? [],
        ]);
    }

    /**
     * One Chair APVR per academic year (not per program). Path: …/{chairEmail}/{academicYear}
     */
    public function getReportByYear(string $chairEmail, string $academicYear)
    {
        return $this->respondGetReport($chairEmail, $academicYear);
    }

    /**
     * Legacy URL …/{chairEmail}/{programCode}/{academicYear}; program segment is ignored.
     */
    public function getReportLegacy(string $chairEmail, string $programCode, string $academicYear)
    {
        return $this->respondGetReport($chairEmail, $academicYear);
    }

    public function upsertReportByYear(Request $request, string $chairEmail, string $academicYear)
    {
        return $this->respondUpsertReport($request, $chairEmail, $academicYear);
    }

    public function upsertReportLegacy(Request $request, string $chairEmail, string $programCode, string $academicYear)
    {
        return $this->respondUpsertReport($request, $chairEmail, $academicYear);
    }

    public function deleteReportByYear(string $chairEmail, string $academicYear)
    {
        return $this->respondDeleteReport($chairEmail, $academicYear);
    }

    public function deleteReportLegacy(string $chairEmail, string $programCode, string $academicYear)
    {
        return $this->respondDeleteReport($chairEmail, $academicYear);
    }

    private function getOrCreateOwnerDocument(string $email): array
    {
        $doc = FirestoreService::getDocument($this->collectionName, $email);
        if ($doc !== null) {
            $reports = $doc['reports'] ?? [];
            if (is_array($reports)) {
                $deduped = $this->dedupeReportsByAcademicYear($reports);
                if (count($deduped) !== count($reports)) {
                    $this->saveOwnerReports($email, $deduped);

                    return array_merge($doc, ['reports' => $deduped]);
                }
            }

            return $doc;
        }

        $payload = ['email' => $email, 'reports' => []];
        FirestoreService::firestore()
            ->collection($this->collectionName)
            ->document($email)
            ->set($payload, ['merge' => true]);

        return $payload;
    }

    private function saveOwnerReports(string $email, array $reports): void
    {
        FirestoreService::firestore()
            ->collection($this->collectionName)
            ->document($email)
            ->set(['email' => $email, 'reports' => $reports], ['merge' => true]);
    }

    private function findReportIndexByYear(array $reports, string $academicYear): int
    {
        $want = trim($academicYear);
        foreach ($reports as $index => $report) {
            if (! is_array($report)) {
                continue;
            }
            $details = $report['header'] ?? [];
            $candidateYear = (string) ($details['academicYearAppliesTo'] ?? '');
            if (strcasecmp($candidateYear, $want) === 0) {
                return $index;
            }
        }

        return -1;
    }

    /**
     * Collapse multiple Firestore rows for the same academic year (legacy per-program) to one.
     *
     * @param  list<mixed>  $reports
     * @return list<mixed>
     */
    private function dedupeReportsByAcademicYear(array $reports): array
    {
        $byYear = [];
        $noYear = [];
        foreach ($reports as $report) {
            if (! is_array($report)) {
                continue;
            }
            $y = trim((string) (($report['header'] ?? [])['academicYearAppliesTo'] ?? ''));
            if ($y === '') {
                $noYear[] = $report;

                continue;
            }
            $k = strtolower($y);
            if (! isset($byYear[$k])) {
                $byYear[$k] = [];
            }
            $byYear[$k][] = $report;
        }
        $out = [];
        foreach ($byYear as $list) {
            if (count($list) === 1) {
                $out[] = $list[0];

                continue;
            }
            $winner = $list[0];
            $bestTs = strtotime((string) ($winner['updatedAt'] ?? '')) ?: 0;
            foreach (array_slice($list, 1) as $cand) {
                $ts = strtotime((string) ($cand['updatedAt'] ?? '')) ?: 0;
                if ($ts >= $bestTs) {
                    $winner = $cand;
                    $bestTs = $ts;
                }
            }
            $out[] = $winner;
        }
        foreach ($noYear as $r) {
            $out[] = $r;
        }

        return $out;
    }

    private function respondGetReport(string $chairEmail, string $academicYear)
    {
        $doc = $this->getOrCreateOwnerDocument($chairEmail);
        $reports = $doc['reports'] ?? [];
        $idx = $this->findReportIndexByYear($reports, $academicYear);

        if ($idx === -1) {
            $report = $this->buildDefaultReport($chairEmail, $academicYear);
            $reports[] = $report;
            $this->saveOwnerReports($chairEmail, $reports);

            return response()->json([
                'success' => true,
                'data' => $report,
                'meta' => ['initialized' => true],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $reports[$idx],
            'meta' => ['initialized' => false],
        ]);
    }

    private function respondUpsertReport(Request $request, string $chairEmail, string $academicYear)
    {
        $validator = Validator::make($request->all(), [
            'header' => 'sometimes|array',
            'textBlocks' => 'sometimes|array',
            'table1' => 'sometimes|array',
            'table3' => 'sometimes|array',
            'table4' => 'sometimes|array',
            'table5' => 'sometimes|array',
            'table6' => 'sometimes|array',
            'table7MeanCgpa' => 'sometimes|array',
            'table8GradeDistribution' => 'sometimes|array',
            'table9FacultyQuality' => 'sometimes|array',
            'table10Resources' => 'sometimes|array',
            'table11PdActivities' => 'sometimes|array',
            'table13' => 'sometimes|array',
            'table14' => 'sometimes|array',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $doc = $this->getOrCreateOwnerDocument($chairEmail);
        $reports = $doc['reports'] ?? [];
        $idx = $this->findReportIndexByYear($reports, $academicYear);
        if ($idx === -1) {
            $reports[] = $this->buildDefaultReport($chairEmail, $academicYear);
            $idx = count($reports) - 1;
        }

        $existing = is_array($reports[$idx]) ? $reports[$idx] : [];
        $patch = $request->only(['header', 'textBlocks', 'table1', 'table3', 'table4', 'table5', 'table6', 'table7MeanCgpa', 'table8GradeDistribution', 'table9FacultyQuality', 'table10Resources', 'table11PdActivities', 'table13', 'table14']);
        $updated = array_merge($existing, $patch);
        $updated['header'] = array_merge($existing['header'] ?? [], $patch['header'] ?? []);
        $updated['updatedAt'] = now()->toIso8601String();

        $reports[$idx] = $updated;
        $this->saveOwnerReports($chairEmail, $reports);

        return response()->json([
            'success' => true,
            'data' => $updated,
        ]);
    }

    private function respondDeleteReport(string $chairEmail, string $academicYear)
    {
        $doc = $this->getOrCreateOwnerDocument($chairEmail);
        $reports = $doc['reports'] ?? [];
        $idx = $this->findReportIndexByYear($reports, $academicYear);
        if ($idx === -1) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found',
            ], 404);
        }

        array_splice($reports, $idx, 1);
        $this->saveOwnerReports($chairEmail, $reports);

        return response()->json([
            'success' => true,
            'message' => 'Report deleted successfully',
        ]);
    }

    private function buildDefaultReport(string $chairEmail, string $academicYear): array
    {
        return [
            'reportId' => trim($academicYear),
            'header' => [
                'chairEmail' => $chairEmail,
                'programCode' => '',
                'department' => '',
                'departmentChair' => '',
                'facultyDean' => '',
                'submissionDate' => '',
                'personCompletingName' => '',
                'personCompletingPosition' => '',
                'academicYearAppliesTo' => trim($academicYear),
            ],
            'textBlocks' => [
                'sectionAObjectives' => '',
                'sectionAOperations' => '',
                'sectionAAcademicPersonnel' => '',
                'sectionDSurveyInstrumentType' => '',
                'sectionDSurveyInstrumentOption' => 'survey',
                'sectionDSurveyInstrumentOther' => '',
                'sectionDEffectiveness' => '',
                'sectionDRecommendations' => '',
                'sectionGDifficulties' => '',
                'sectionGImpact' => '',
                'sectionGProposedActions' => '',
                'sectionGCampusBelmopanMain' => '',
                'sectionGCampusBelizeCity' => '',
                'sectionGCampusPuntaGordaTown' => '',
                'sectionGCampusCentralFarm' => '',
                'sectionHInternalChanges' => '',
                'sectionHExternalChanges' => '',
                'sectionHImplications' => '',
                'sectionJProgramStructureChanges' => '',
                'sectionJCourseChanges' => '',
                'sectionJDevelopmentActivities' => '',
                'sectionLResearchList' => '',
                'sectionLServiceActivitiesList' => '',
                'sectionLReflection' => '',
                'sectionLResearchFacultyOnlyCount' => '',
                'sectionLResearchStudentOnlyCount' => '',
                'sectionLResearchStudentFacultyJointCount' => '',
                'sectionLResearchDepartmentTotalCount' => '',
                'sectionLServiceExternalCount' => '',
                'sectionLServiceInternalCount' => '',
                'sectionLServiceTotalCount' => '',
                'sectionLTableIt' => '',
                'sectionFPhysicalResourcesAdequacy' => '',
                'sectionFMaterialResourcesAdequacy' => '',
                'sectionICourseEvalParticipated' => '',
                'sectionIParticipationCount' => '',
                'sectionICourseEvalSummary01' => '',
                'sectionICourseEvalSummary02' => '',
                'sectionICourseEvalSummary03' => '',
                'sectionICourseEvaluations' => '',
                'sectionIOtherChallenges' => '',
                'sectionIOtherConstraints' => '',
                'sectionIOtherRecommendations' => '',
                'sectionIOtherOutreach' => '',
                'sectionIOtherCommunityService' => '',
                'sectionIOtherResearch' => '',
                'sectionIOtherProfessionalDevelopment' => '',
                'sectionLAlertLabelNotes' => '',
            ],
            'table1' => [[
                'program' => '',
                'studentsRegistered' => '',
                'studentsEnrolled' => '',
            ]],
            'table3' => [[
                'program' => '',
                'studentsStarted' => '',
                'studentsCompleted' => '',
                'finalYearCompleted' => '',
            ]],
            'table4' => [[
                'program' => '',
                'currentYearStudentIntake' => '',
                'currentYearFirstYearPersistenceRate' => '',
                'priorYear1StudentIntake' => '',
                'priorYear1FirstYearPersistenceRate' => '',
                'priorYear2StudentIntake' => '',
                'priorYear2FirstYearPersistenceRate' => '',
                'yearAverage' => '',
            ]],
            'table5' => [[
                'year' => '',
                'program' => '',
                'studentIntake' => '',
                'graduatedWithinNormalTime' => '',
                'graduatedWithin150Percent' => '',
                'graduatedToDate' => '',
            ]],
            'table6' => [[
                'program' => '',
                'graduatesPast3Years' => '',
                'surveyRespondents' => '',
                'employedPct' => '',
                'unemployedPct' => '',
                'furtherStudyPct' => '',
            ]],
            'table7MeanCgpa' => [
                'yearLabels' => ['Year 1', 'Year 2', 'Year 3', 'Year 4'],
                'rows' => [
                    [
                        'program' => '',
                        'values' => ['', '', '', ''],
                    ],
                ],
            ],
            'table8GradeDistribution' => [
                ['grade' => 'A', 'numberOfStudents' => ''],
                ['grade' => 'A-', 'numberOfStudents' => ''],
                ['grade' => 'B+', 'numberOfStudents' => ''],
                ['grade' => 'B', 'numberOfStudents' => ''],
                ['grade' => 'C+', 'numberOfStudents' => ''],
                ['grade' => 'C', 'numberOfStudents' => ''],
                ['grade' => 'D+', 'numberOfStudents' => ''],
                ['grade' => 'D', 'numberOfStudents' => ''],
                ['grade' => 'F', 'numberOfStudents' => ''],
            ],
            'table9FacultyQuality' => [
                ['category' => 'employment status', 'level' => 'Full-time', 'count' => ''],
                ['category' => 'employment status', 'level' => 'Adjunct', 'count' => ''],
                ['category' => 'employment status', 'level' => 'Total', 'count' => ''],
                ['category' => 'academic rank (Full-time & adjunct)', 'level' => 'Professor', 'count' => ''],
                ['category' => 'academic rank (Full-time & adjunct)', 'level' => 'Associate Professor', 'count' => ''],
                ['category' => 'academic rank (Full-time & adjunct)', 'level' => 'Assistant Professor', 'count' => ''],
                ['category' => 'academic rank (Full-time & adjunct)', 'level' => 'Senior Instructor', 'count' => ''],
                ['category' => 'academic rank (Full-time & adjunct)', 'level' => 'Instructor', 'count' => ''],
                ['category' => 'academic rank (Full-time & adjunct)', 'level' => 'Other', 'count' => ''],
                ['category' => 'academic rank (Full-time & adjunct)', 'level' => 'Total', 'count' => ''],
                ['category' => 'Faculty highest earned qualification (full-time & adjunct)', 'level' => 'Post-doctoral', 'count' => ''],
                ['category' => 'Faculty highest earned qualification (full-time & adjunct)', 'level' => 'Doctoral', 'count' => ''],
                ['category' => 'Faculty highest earned qualification (full-time & adjunct)', 'level' => 'Master\'s', 'count' => ''],
                ['category' => 'Faculty highest earned qualification (full-time & adjunct)', 'level' => 'Postgraduate', 'count' => ''],
                ['category' => 'Faculty highest earned qualification (full-time & adjunct)', 'level' => 'Bachelor\'s', 'count' => ''],
                ['category' => 'Faculty highest earned qualification (full-time & adjunct)', 'level' => 'Sub-bachelor\'s', 'count' => ''],
                ['category' => 'Faculty highest earned qualification (full-time & adjunct)', 'level' => 'Total', 'count' => ''],
                ['category' => 'Level of courses taught relative to highest earned qualification (Full-time & adjunct)', 'level' => 'Earned qualification above the level taught courses', 'count' => ''],
                ['category' => 'Level of courses taught relative to highest earned qualification (Full-time & adjunct)', 'level' => 'Earned qualification below the level of taught courses', 'count' => ''],
                ['category' => 'Level of courses taught relative to highest earned qualification (Full-time & adjunct)', 'level' => 'Total', 'count' => ''],
                ['category' => 'Faculty annual teaching load (full-time only)', 'level' => 'Above 10 courses', 'count' => ''],
                ['category' => 'Faculty annual teaching load (full-time only)', 'level' => '8-10 courses', 'count' => ''],
                ['category' => 'Faculty annual teaching load (full-time only)', 'level' => '6-8 courses', 'count' => ''],
                ['category' => 'Faculty annual teaching load (full-time only)', 'level' => 'Under 6 courses', 'count' => ''],
                ['category' => 'Faculty annual teaching load (full-time only)', 'level' => 'No teaching', 'count' => ''],
                ['category' => 'Faculty annual teaching load (full-time only)', 'level' => 'Total', 'count' => ''],
            ],
            'table10Resources' => [
                'physicalClassroom' => ['', '', '', '', '', '', '', '', '', ''],
                'physicalLaboratory' => ['', '', '', '', '', '', '', '', '', ''],
                'physicalInformationTechnology' => ['', '', '', '', '', '', '', '', '', ''],
                'materialInstructionalSupplies' => ['', '', '', '', '', '', '', '', '', ''],
                'materialSpecializedEquipment' => ['', '', '', '', '', '', '', '', '', ''],
            ],
            'table11PdActivities' => [[
                'activityProvided' => '',
                'teachingStaffCount' => '',
                'otherStaffCount' => '',
            ]],
            'table13' => [[
                'action' => '',
                'timeframe' => '',
                'responsible' => '',
                'progress' => '',
            ]],
            'table14' => [[
                'action' => '',
                'timeframe' => '',
                'responsible' => '',
                'progress' => '',
            ]],
            'updatedAt' => now()->toIso8601String(),
        ];
    }
}

