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
                    "name" => $faculty['user']['name'] ?? $faculty['user'] ?? 'N/A',
                    "formSubmitted" => $faculty['formSubmitted'] ?? []
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
                    "name" => $finance['user']['name'] ?? $finance['name'] ?? 'N/A',
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
                    "name" => $hr['user']['name'] ?? $hr['name'] ?? 'N/A',
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
                    "name" => $record['user']['name'] ?? $record['name'] ?? 'N/A',
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
                    "name" => $staff['user']['name'] ?? $staff['name'] ?? 'N/A',
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
}
