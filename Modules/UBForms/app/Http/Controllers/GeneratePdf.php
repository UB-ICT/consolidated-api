<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Staff;

class GeneratePdf extends Controller
{
    //
    public function generatePdf(Request $request, string $reportID){

                // Fetch data from MongoDB based on report ID
                $report = Staff::find($reportID);

                // return $report;

                if (!$report) {
                    return response()->json(['error' => 'Report not found'], 404);
                }
        
                // Generate PDF using data directly
                // $pdf = PDF::loadHTML($this->generateReportPdfHtml($report));
                $pdf = PDF::loadView('pdfReport', ['report' => $report]);
        
                // Return PDF as a response
                return $pdf->download('report_' . $report->id . '.pdf');
            }
/*  
    private function generateReportPdfHtml($report){

                // return "<h1>Testing Report</h1>";

                // University of Belize colors
                $headerBackgroundColor = '#7e317b'; // Light purple
                $headerTextColor = '#ffd700'; // Gold
                $bodyBackgroundColor = '#f5f5f5'; // Light gray background for content

                // Determine report type (assuming you have a field like 'type' in your report)
                //$reportType = $report->type === 'faculty' ? 'Faculty Annual Report' : 'Staff Annual Report';

                // Determine report type based on fields in $report object
                if (isset($report->faculty)) {
                    $reportType = 'Faculty Annual Report';

                } elseif (isset($report->department)) {
                    $reportType = 'Staff Annual Report';

                } else {
                    $reportType = 'Unknown Report Type'; // Handle if neither faculty nor department is set
                }               

                // Start generating HTML content for PDF based on report data

                // Header section with University of Belize colors
                $html = "";
                $html .= '<div style="background-color: ' . $headerBackgroundColor . '; color: ' . $headerTextColor . '; padding: 10px; text-align: center;">';
                $html = '<html><head><title>' . $reportType . '</title></head><body style="background-color: ' . $bodyBackgroundColor . ';">';

                //$html .= '<h1>' . $reportType . '</h1>';
                //$html .= '<h3>Report ID: ' . $report->id . '</h3>';
                $html .= '</div>';

                // Content section
                // $html .= '<div style="padding: 20px;">';
                // $html .= '<p><strong>Title:</strong> ' . $report->title . '</p>';
                // $html .= '<p><strong>Description:</strong> ' . $report->description . '</p>';
                //$html .= '<p><strong>Date:</strong> ' . $report->date . '</p>';

                // Dynamic fields based on the report object
                if (isset($report->fields) && is_array($report->fields)) {
                    foreach ($report->fields as $field) {
                        $html .= '<p><strong>' . $field['label'] . ':</strong> ' . $field['value'] . '</p>';
                    }
                }

                $html .= '</div>';

                $html .= '</body></html>';

                return $html;
    }*/

}



