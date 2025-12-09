<?php

namespace Modules\Xenegrade\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class XenegradeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('xenegrade::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('xenegrade::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('xenegrade::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('xenegrade::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Check email roles in spreadsheet
     * 
     * @param Request $request
     * @param string $email
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkEmailRoles(Request $request, string $email)
    {
        try {
            // Validate email format
            $validator = Validator::make(['email' => $email], [
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email format',
                    'errors' => $validator->errors()
                ], 422);
            }

            $spreadsheetId = env('GOOGLE_SHEET_ID'); // Add your sheet ID to .env
            $range = env('GOOGLE_SHEET_RANGE', 'Sheet2!A1:Z2000');

            logger('spreadsheetId: ' . $spreadsheetId);

            if (!$spreadsheetId) {
                Log::error('Google Sheet ID not configured for email role check', ['email' => $email]);
                return response()->json([
                    'success' => false,
                    'message' => 'Google Sheet ID not configured. Please set GOOGLE_SHEET_ID in your .env file',
                    'data' => null
                ], 500);
            }

            $roles = GoogleSheetService::checkEmailRoles($spreadsheetId, $range, $email);

            return response()->json([
                'success' => true,
                'message' => 'Email roles retrieved successfully',
                'data' => $roles
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error checking email roles', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking email roles',
                'data' => null
            ], 500);
        }
    }

    /**
     * Get courses by course coordinator email
     * Returns courseCode, courseId (alias for courseCode), courseSection, and courseName (as courseTitle)
     * 
     * @param Request $request
     * @param string $email
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCoursesByCoordinator(Request $request, string $email)
    {
        try {
            // Validate email format
            $validator = Validator::make(['email' => $email], [
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email format',
                    'errors' => $validator->errors()
                ], 422);
            }

            $spreadsheetId = env('GOOGLE_SHEET_ID');
            $range = env('GOOGLE_SHEET_RANGE', 'Sheet2!A1:Z2000');

            if (!$spreadsheetId) {
                Log::error('Google Sheet ID not configured for coordinator courses', ['email' => $email]);
                return response()->json([
                    'success' => false,
                    'message' => 'Google Sheet ID not configured. Please set GOOGLE_SHEET_ID in your .env file',
                    'data' => []
                ], 500);
            }

            $courses = GoogleSheetService::getCoursesByCoordinatorEmail($spreadsheetId, $range, $email);

            return response()->json([
                'success' => true,
                'message' => 'Courses retrieved successfully',
                'data' => $courses
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error getting courses by coordinator', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving courses',
                'data' => []
            ], 500);
        }
    }

    /**
     * Get courses by program coordinator email
     * Returns courseCode, courseId (alias for courseCode), courseSection, and courseName (as courseTitle)
     * Only returns courses that don't have a CourseCoordinator assigned
     * 
     * @param Request $request
     * @param string $email
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCoursesByProgramCoordinator(Request $request, string $email)
    {
        try {
            // Validate email format
            $validator = Validator::make(['email' => $email], [
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email format',
                    'errors' => $validator->errors()
                ], 422);
            }

            $spreadsheetId = env('GOOGLE_SHEET_ID');
            $range = env('GOOGLE_SHEET_RANGE', 'Sheet2!A1:Z2000');

            if (!$spreadsheetId) {
                Log::error('Google Sheet ID not configured for program coordinator courses', ['email' => $email]);
                return response()->json([
                    'success' => false,
                    'message' => 'Google Sheet ID not configured. Please set GOOGLE_SHEET_ID in your .env file',
                    'data' => []
                ], 500);
            }

            $courses = GoogleSheetService::getCoursesByProgramCoordinatorEmail($spreadsheetId, $range, $email);

            return response()->json([
                'success' => true,
                'message' => 'Courses retrieved successfully',
                'data' => $courses
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error getting courses by program coordinator', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving courses',
                'data' => []
            ], 500);
        }
    }
}
