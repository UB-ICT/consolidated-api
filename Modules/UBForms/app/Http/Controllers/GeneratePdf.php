<?php

namespace Modules\UBForms\Http\Controllers;


use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\UBForms\Models\Staff;

class GeneratePdf extends Controller
{
    //
    public function generatePdf(Request $request, string $reportID)
    {

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
}



