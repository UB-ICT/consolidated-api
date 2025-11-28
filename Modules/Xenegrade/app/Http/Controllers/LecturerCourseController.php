<?php

namespace Modules\Xenegrade\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;


class LecturerCourseController extends Controller
{
    public function getLecturerCourses(Request $request, string $email, string $semester)
    {
        // 1. Find lecturer in staff table
        $lecturer = DB::table('staff')
            ->where('staEmail', $email)
            ->where('staType', 'Instructor')
            ->first();

        if (!$lecturer) {
            return response()->json(['error' => 'Lecturer not found'], 404);
        }

        // 2. Get courses from vinSections and include course title from COURSE table
        $courses = DB::table('vinSections as vs')
            ->join('COURSE as c', 'vs.CourseID', '=', 'c.couCourseID')
            ->where('vs.Session', $semester)
            ->where('vs.StaffFName1', $lecturer->staFName)
            ->where('vs.StaffLName1', $lecturer->staLName)
            ->select(
                'vs.couID',
                'vs.CourseID',
                'vs.CourseCode',
                // 'vs.sesID',
                'vs.Session',
                'vs.StaffFName1',
                'vs.StaffLName1',
                'c.couTitle as CourseTitle' // Include course title
            )
            ->get();

        return response()->json([
            'lecturer' => [
                // 'firstName' => $lecturer->staFName,
                // 'lastName' => $lecturer->staLName,
                'email' => $lecturer->staEmail,
            ],
            'semester' => $semester,
            'courses' => $courses
        ]);
    }
}
