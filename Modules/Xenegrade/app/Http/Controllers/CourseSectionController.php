<?php

namespace Modules\Xenegrade\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Services\GoogleSheetService;

class CourseSectionController extends Controller
{
    public function getCourseRows($courseCode, $courseSection)
    {
        $spreadsheetId = env('GOOGLE_SHEET_ID'); // Add your sheet ID to .env
        $range = env('COURSES_GOOGLE_SHEET_RANGE', 'courses!A1:Z2000');

        $rows = GoogleSheetService::getRowsByCourse($spreadsheetId, $range, $courseCode, $courseSection);

        if (empty($rows)) {
            return response()->json(['message' => 'No rows found'], 404);
        }

        return response()->json($rows);
    }


}
