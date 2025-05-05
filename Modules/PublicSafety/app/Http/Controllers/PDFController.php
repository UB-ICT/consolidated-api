<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\IncidentReport;

class PDFController extends Controller
{
    public function downloadIncidentReport($id)
    {
        // Fetch the incident report from the database
        $incidentReport = IncidentReport::find($id);

        if (!$incidentReport) {
            return response()->json(['error' => 'Incident Report not found'], 404);
        }

        // Load the view and pass data
        $pdf = Pdf::loadView('pdf.incident_report', compact('incidentReport'));

        // Download PDF
        return $pdf->download('incident_report_' . $id . '.pdf');
    }
}
