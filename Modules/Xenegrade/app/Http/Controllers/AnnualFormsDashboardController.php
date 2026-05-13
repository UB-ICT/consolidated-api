<?php

namespace Modules\Xenegrade\Http\Controllers;

use App\Services\FirestoreService;
use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Xenegrade\Services\AnnualFormsDashboardService;

class AnnualFormsDashboardController extends Controller
{
    public function show(Request $request, string $email)
    {
        $validator = Validator::make(
            array_merge($request->query(), ['email' => $email]),
            [
                'email' => 'required|email',
                'academicYear' => 'nullable|string',
                'semester' => 'nullable|string',
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = FirestoreService::getDocument('cmon_courseMonitoringSettings', 'global') ?? [];
        $academicYear = trim((string) $request->query('academicYear', (string) ($settings['currentYear'] ?? '')));
        $semester = trim((string) $request->query('semester', (string) ($settings['currentSemester'] ?? '')));

        if ($academicYear === '' || $semester === '') {
            return response()->json([
                'success' => false,
                'message' => 'academicYear and semester are required (or configure cmon_courseMonitoringSettings/global with currentYear and currentSemester).',
            ], 422);
        }

        $spreadsheetId = env('GOOGLE_SHEET_ID');
        $range = env('COURSES_GOOGLE_SHEET_RANGE', 'courses!A1:Z2000');
        $roles = $spreadsheetId
            ? GoogleSheetService::checkEmailRoles($spreadsheetId, $range, $email)
            : [
                'lecturer' => false,
                'courseCoordinator' => false,
                'programCoordinator' => false,
                'chair' => false,
                'dean' => false,
                'VP' => false,
            ];

        $service = new AnnualFormsDashboardService;
        $data = $service->buildDashboardPayload($email, $academicYear, $semester, $roles);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
