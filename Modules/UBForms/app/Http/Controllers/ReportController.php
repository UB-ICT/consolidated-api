<?php

namespace Modules\UBForms\Http\Controllers;

use App\Services\FirestoreService;

class ReportController extends Controller
{
    public function getReports($reportTypes)
    {
        $reportTypes = explode("-", $reportTypes);
        $report = [];

        // Get Faculty Report and add data to $report
        if (in_array('faculty', $reportTypes)) {
            $facultyData = FirestoreService::getCollection('faculties');
            foreach ($facultyData as $faculty) {
                array_push($report, [
                    "reportType" => 'faculties',
                    "id" => $faculty['_id'] ?? $faculty['id'] ?? null,
                    "name" => $faculty['users']['name'] ?? $faculty['name'] ?? 'N/A',
                    "formSubmitted" => $faculty['formSubmitted'] ?? []/*  */
                ]);
            }
        }

        // Get Finance Report and add data to $report
        if (in_array('finance', $reportTypes)) {
            $financeData = FirestoreService::getCollection('FinanceStatistics');
            foreach ($financeData as $finance) {
                array_push($report, [
                    "reportType" => 'FinanceStatistics',
                    "id" => $finance['_id'] ?? $finance['id'] ?? null,
                    "name" => $finance['users']['name'] ?? $finance['name'] ?? 'N/A',
                    "formSubmitted" => $finance['formSubmitted'] ?? []
                ]);
            }
        }

        // Get Human Resources Report and add data to $report
        if (in_array('human_resources', $reportTypes)) {
            $humanResourcesData = FirestoreService::getCollection('HRStatistics');
            foreach ($humanResourcesData as $hr) {
                array_push($report, [
                    "reportType" => 'HRStatistics',
                    "id" => $hr['_id'] ?? $hr['id'] ?? null,
                    "name" => $hr['users']['name'] ?? $hr['name'] ?? 'N/A',
                    "formSubmitted" => $hr['formSubmitted'] ?? []
                ]);
            }
        }

        // Get Records Report and add data to $report
        if (in_array('records', $reportTypes)) {
            $recordsData = FirestoreService::getCollection('recordsStatistics');
            foreach ($recordsData as $record) {
                array_push($report, [
                    "reportType" => 'records',
                    "id" => $record['_id'] ?? $record['id'] ?? null,
                    "name" => $record['users']['name'] ?? $record['name'] ?? 'N/A',
                    "formSubmitted" => $record['formSubmitted'] ?? []
                ]);
            }
        }

        // Get Staff Report and add data to $report
        if (in_array('staff', $reportTypes)) {
            $staffData = FirestoreService::getCollection('staff');
            foreach ($staffData as $staff) {
                array_push($report, [
                    "reportType" => 'staff',
                    "id" => $staff['_id'] ?? $staff['id'] ?? null,
                    "name" => $staff['users']['name'] ?? $staff['name'] ?? 'N/A',
                    "formSubmitted" => $staff['formSubmitted'] ?? []
                ]);
            }
        }

        // Return the list of reports
        $response = [
            'success' => true,
            'message' => 'Reports Retrieved.',
            'data' => [
                'report' => $report
            ],
        ];
        return response()->json($response, 200);
    }

    public function getReportsByAcademicYear($reportTypes, $academicYearID)
    {
        $reportTypes = explode("-", $reportTypes);
        $report = [];
        // Get Faculty Report filtered by academic year
        if (in_array('faculty', $reportTypes)) {
            $facultyData = FirestoreService::queryCollection(
                'faculties',
                'academicYearID',
                '==',
                $academicYearID
            );
            foreach ($facultyData as $faculty) {
                array_push($report, [
                    "reportType" => 'faculties',
                    "id" => $faculty['_id'] ?? $faculty['id'] ?? null,
                    "name" => $faculty['users']['name'] ?? $faculty['name'] ?? 'N/A',
                    "academicYearID" => $faculty['academicYearID'] ?? $academicYearID,
                    "formSubmitted" => $faculty['formSubmitted'] ?? []
                ]);
            }
        }
        // Get Finance Report filtered by academic year
        if (in_array('finance', $reportTypes)) {
            $financeData = FirestoreService::queryCollection(
                'FinanceStatistics',
                'academicYearID',
                '==',
                $academicYearID
            );
            foreach ($financeData as $finance) {
                array_push($report, [
                    "reportType" => 'FinanceStatistics',
                    "id" => $finance['_id'] ?? $finance['id'] ?? null,
                    "name" => $finance['users']['name'] ?? $finance['name'] ?? 'N/A',
                    "academicYearID" => $finance['academicYearID'] ?? $academicYearID,
                    "formSubmitted" => $finance['formSubmitted'] ?? []
                ]);
            }
        }
        // Get Human Resources Report filtered by academic year
        if (in_array('human_resources', $reportTypes)) {
            $humanResourcesData = FirestoreService::queryCollection(
                'HRStatistics',
                'academicYearID',
                '==',
                $academicYearID
            );
            foreach ($humanResourcesData as $hr) {
                array_push($report, [
                    "reportType" => 'HRStatistics',
                    "id" => $hr['_id'] ?? $hr['id'] ?? null,
                    "name" => $hr['users']['name'] ?? $hr['name'] ?? 'N/A',
                    "academicYearID" => $hr['academicYearID'] ?? $academicYearID,
                    "formSubmitted" => $hr['formSubmitted'] ?? []
                ]);
            }
        }
        // Get Records Report filtered by academic year
        if (in_array('records', $reportTypes)) {
            $recordsData = FirestoreService::queryCollection(
                'recordsStatistics',
                'academicYearID',
                '==',
                $academicYearID
            );
            foreach ($recordsData as $record) {
                array_push($report, [
                    "reportType" => 'records',
                    "id" => $record['_id'] ?? $record['id'] ?? null,
                    "name" => $record['users']['name'] ?? $record['name'] ?? 'N/A',
                    "academicYearID" => $record['academicYearID'] ?? $academicYearID,
                    "test" => $record ?? 'N/A',
                    "formSubmitted" => $record['formSubmitted'] ?? []
                ]);
            }
        }
        // Get Staff Report filtered by academic year
        if (in_array('staff', $reportTypes)) {
            $staffData = FirestoreService::queryCollection(
                'staff',
                'academicYearID',
                '==',
                $academicYearID
            );
            foreach ($staffData as $staff) {
                array_push($report, [
                    "reportType" => 'staff',
                    "id" => $staff['_id'] ?? $staff['id'] ?? null,
                    "name" => $staff['users']['name'] ?? $staff['name'] ?? 'N/A',
                    "academicYearID" => $staff['academicYearID'] ?? $academicYearID,
                    "formSubmitted" => $staff['formSubmitted'] ?? []
                ]);
            }
        }
        // Return the filtered reports
        $response = [
            'success' => true,
            'message' => 'Reports filtered by academic year retrieved.',
            'data' => [
                'academicYearID' => $academicYearID,
                'report' => $report
            ],
        ];
        return response()->json($response, 200);
    }

    public function getTotalFormSubmissionsByAcademicYear($reportTypes, $academicYearID)
    {
        $reportTypes = explode("-", $reportTypes);
        $submissionCounts = [
            'faculty' => 0,
            'finance' => 0,
            'human_resources' => 0,
            'records' => 0,
            'staff' => 0,
            'total' => 0
        ];

        // Count Faculty form submissions
        if (in_array('faculty', $reportTypes)) {
            $facultyData = FirestoreService::queryCollection(
                'faculties',
                'academicYearID',
                '==',
                $academicYearID
            );
            foreach ($facultyData as $faculty) {
                if (!empty($faculty['formSubmitted']) && $faculty['formSubmitted'] === true) {
                    $submissionCounts['faculty']++;
                    $submissionCounts['total']++;
                }
            }
        }

        // Count Finance form submissions
        if (in_array('finance', $reportTypes)) {
            $financeData = FirestoreService::queryCollection(
                'FinanceStatistics',
                'academicYearID',
                '==',
                $academicYearID
            );
            foreach ($financeData as $finance) {
                if (!empty($finance['formSubmitted']) && $finance['formSubmitted'] === true) {
                    $submissionCounts['finance']++;
                    $submissionCounts['total']++;
                }
            }
        }

        // Count Human Resources form submissions
        if (in_array('human_resources', $reportTypes)) {
            $humanResourcesData = FirestoreService::queryCollection(
                'HRStatistics',
                'academicYearID',
                '==',
                $academicYearID
            );
            foreach ($humanResourcesData as $hr) {
                if (!empty($hr['formSubmitted']) && $hr['formSubmitted'] === true) {
                    $submissionCounts['human_resources']++;
                    $submissionCounts['total']++;
                }
            }
        }

        // Count Records form submissions
        if (in_array('records', $reportTypes)) {
            $recordsData = FirestoreService::queryCollection(
                'recordsStatistics',
                'academicYearID',
                '==',
                $academicYearID
            );
            foreach ($recordsData as $record) {
                if (!empty($record['formSubmitted']) && $record['formSubmitted'] === true) {
                    $submissionCounts['records']++;
                    $submissionCounts['total']++;
                }
            }
        }

        // Count Staff form submissions
        if (in_array('staff', $reportTypes)) {
            $staffData = FirestoreService::queryCollection(
                'staff',
                'academicYearID',
                '==',
                $academicYearID
            );
            foreach ($staffData as $staff) {
                if (!empty($staff['formSubmitted']) && $staff['formSubmitted'] === true) {
                    $submissionCounts['staff']++;
                    $submissionCounts['total']++;
                }
            }
        }

        // Return the counts
        $response = [
            'success' => true,
            'message' => 'Form submission counts by academic year retrieved.',
            'data' => [
                'academicYearID' => $academicYearID,
                'counts' => $submissionCounts
            ],
        ];
        return response()->json($response, 200);
    }
}
