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
            $client->setAuthConfig(storage_path(env('GOOGLE_SHEET_CREDENTIALS')));
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
                array_pad($row, count($headers), null)
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
        if (empty($spreadsheetId)) {
            Log::error('Google Sheet ID is empty in checkEmailRoles', ['email' => $email]);
            return [
                'isLecturer' => false,
                'CourseCoordinator' => false,
                'ProgramCoordinator' => false,
                'chair' => false,
                'dean' => false,
            ];
        }

        $rows = self::readSheet($spreadsheetId, $range);

        if (empty($rows)) {
            return [
                'isLecturer' => false,
                'CourseCoordinator' => false,
                'ProgramCoordinator' => false,
                'chair' => false,
                'dean' => false,
                'error' => 'No rows found',
            ];
        }

        $headers = $rows[0];
        $dataRows = array_slice($rows, 1);

        // Initialize result array
        $result = [
            'isLecturer' => false,
            'CourseCoordinator' => false,
            'ProgramCoordinator' => false,
            'chair' => false,
            'dean' => false,
        ];

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
                $result['isLecturer'] = true;
            }

            // Check CourseCoordinator column
            if ($courseCoordinatorIndex !== false && 
                isset($paddedRow[$courseCoordinatorIndex]) && 
                strtolower(trim($paddedRow[$courseCoordinatorIndex])) === strtolower(trim($email))) {
                $result['CourseCoordinator'] = true;
            }

            // Check ProgramCoordinator column
            if ($programCoordinatorIndex !== false && 
                isset($paddedRow[$programCoordinatorIndex]) && 
                strtolower(trim($paddedRow[$programCoordinatorIndex])) === strtolower(trim($email))) {
                $result['ProgramCoordinator'] = true;
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
     * Get courses by course coordinator email
     * Returns courseCode, courseId (alias for courseCode), courseSection, and courseName (as courseTitle)
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
                array_pad($row, count($headers), null)
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

                // Create unique key for this course combination
                $uniqueKey = $courseCode . '|' . $courseSection;

                // Only add if we haven't seen this combination before
                if ($courseCode && $courseSection && !isset($seenCourses[$uniqueKey])) {
                    $seenCourses[$uniqueKey] = true;
                    $courses[] = [
                        'courseCode' => $courseCode,
                        'courseId' => $courseCode, // Alias for courseCode
                        'courseSection' => $courseSection,
                        'courseTitle' => $courseName ?? '', // courseName as courseTitle
                    ];
                }
            }
        }

        return $courses;
    }

    /**
     * Get courses by program coordinator email
     * Returns courseCode, courseId (alias for courseCode), courseSection, and courseName (as courseTitle)
     * Only returns courses that don't have a CourseCoordinator assigned
     */
    public static function getCoursesByProgramCoordinatorEmail(string $spreadsheetId, string $range, string $programCoordinatorEmail): array
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
                array_pad($row, count($headers), null)
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

            // Get program coordinator email (check multiple possible column names)
            $programCoordinatorEmailValue = $getValue($rowAssoc, ['ProgramCoordinator', 'programCoordinator', 'Program Coordinator']);

            // Get course coordinator email to check if it's empty
            $courseCoordinatorValue = $getValue($rowAssoc, ['CourseCoordinator', 'courseCoordinator', 'Course Coordinator']);

            // Check if this row matches the program coordinator email AND has no course coordinator
            if ($programCoordinatorEmailValue && 
                strtolower(trim($programCoordinatorEmailValue)) === strtolower(trim($programCoordinatorEmail)) &&
                empty($courseCoordinatorValue)) {
                
                // Get course fields
                $courseCode = $getValue($rowAssoc, ['CourseCode', 'courseCode', 'Course Code']);
                $courseSection = $getValue($rowAssoc, ['CourseSection', 'courseSection', 'Course Section']);
                $courseName = $getValue($rowAssoc, ['CourseName', 'courseName', 'Course Name', 'CourseTitle', 'courseTitle', 'Course Title']);

                // Create unique key for this course combination
                $uniqueKey = $courseCode . '|' . $courseSection;

                // Only add if we haven't seen this combination before
                if ($courseCode && $courseSection && !isset($seenCourses[$uniqueKey])) {
                    $seenCourses[$uniqueKey] = true;
                    $instructorEmail = $getValue($rowAssoc, ['InstructorEmail', 'instructorEmail', 'Instructor Email']);
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

        return $courses;
    }
 
}
