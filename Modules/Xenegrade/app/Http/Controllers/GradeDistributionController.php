<?php

namespace Modules\Xenegrade\Http\Controllers;

use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Xenegrade\Services\GradeDistributionService;

class GradeDistributionController extends Controller
{
    protected string $settingsCollection = 'cmon_courseMonitoringSettings';

    protected string $settingsDocumentId = 'global';

    /**
     * GET .../gradeDistribution/{courseCode}/{courseSection}?semester=optional
     * When semester is omitted, uses `currentSemester` from Firestore global settings.
     */
    public function show(Request $request, string $courseCode, string $courseSection)
    {
        $validator = Validator::make(
            array_merge($request->query(), [
                'courseCode' => $courseCode,
                'courseSection' => $courseSection,
            ]),
            [
                'courseCode' => 'required|string|max:64',
                'courseSection' => 'required|string|max:64',
                'semester' => 'nullable|string|max:64',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $courseCode = trim($courseCode);
        $courseSection = trim($courseSection);
        $semester = trim((string) $request->query('semester', ''));

        if ($semester === '') {
            $settings = FirestoreService::getDocument($this->settingsCollection, $this->settingsDocumentId);
            if (! is_array($settings)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course monitoring settings not found; cannot resolve current semester.',
                ], 404);
            }
            $semester = trim((string) ($settings['currentSemester'] ?? ''));
        }

        if ($semester === '') {
            return response()->json([
                'success' => false,
                'message' => 'Semester is required (query parameter `semester` or Firestore `currentSemester`).',
            ], 422);
        }

        try {
            $raw = GradeDistributionService::queryRows($semester, $courseCode, $courseSection);
        } catch (\Throwable $e) {
            Log::error('gradeDistribution query failed', [
                'semester' => $semester,
                'courseCode' => $courseCode,
                'courseSection' => $courseSection,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load grade distribution from the student information system.',
            ], 500);
        }

        $rows = [];
        $total = 0;
        foreach ($raw as $row) {
            $arr = (array) $row;
            $letter = (string) ($arr['assGPA'] ?? '');
            $freq = (int) ($arr['Frequency'] ?? $arr['frequency'] ?? 0);
            $total += $freq;
            $rows[] = [
                'letter' => $letter,
                'gradeDescription' => (string) ($arr['Grade Description'] ?? ''),
                'range' => (string) ($arr['Range'] ?? ''),
                'quality' => isset($arr['Quality']) ? (float) $arr['Quality'] : 0.0,
                'frequency' => $freq,
            ];
        }

        foreach ($rows as $i => $r) {
            $rows[$i]['percentage'] = $total > 0
                ? round(($r['frequency'] / $total) * 1000) / 10
                : 0.0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'semester' => $semester,
                'courseCode' => $courseCode,
                'courseSection' => $courseSection,
                'totalStudents' => $total,
                'rows' => $rows,
            ],
        ]);
    }
}
