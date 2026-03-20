<?php

namespace Modules\Xenegrade\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class LecturerCourseController extends Controller
{
    public function getLecturerCourses(Request $request, string $email, string $semester)
    {
        // 1. Find lecturer in staff table (SQL Server)
        $lecturer = DB::connection('sqlsrv')
            ->table('staff')
            ->where('staEmail', $email)
            //#->where('staType', 'Instructor')
            ->first();

        if (!$lecturer) {
            return response()->json(['error' => 'Lecturer not found'], 405);
        }

        // 2. Get courses from vinSections and COURSE tables (SQL Server)
        $courses = DB::connection('sqlsrv')
            ->table('vinSections as vs')
            ->join('COURSE as c', 'vs.CourseID', '=', 'c.couCourseID')
            ->where('vs.Session', $semester)
            ->where('vs.StaffFName1', $lecturer->staFName)
            ->where('vs.StaffLName1', $lecturer->staLName)
            ->where('vs.CourseStatus','Offered')
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

        return response()->json([
            'lecturer' => [
                'email' => $lecturer->staEmail,
            ],
            'semester' => $semester,
            'courses' => $courses
        ]);
    }
}
