<?php

namespace Modules\PublicSafety\Http\Controllers;


use Illuminate\Routing\Controller;
use App\Http\Resources\IncidentReportResource;
use App\Http\Resources\IncidentReportCollection;
use App\Http\Requests\StoreIncidentReportRequest;
use App\Http\Requests\UpdateIncidentReportRequest;
use App\Models\IncidentReport;
use Database\Factories\IncidentReportFactory;
use App\Models\IncidentFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class IncidentReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new IncidentReportCollection(IncidentReport::paginate());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIncidentReportRequest $request)
    {
        return new IncidentReportResource(IncidentReport::create($request->all()));
    }

    /**
     * Display the specified resource.
     */
    public function show(IncidentReport $incidentReport)
    {
        return new IncidentReportResource($incidentReport);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(IncidentReport $incidentReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIncidentReportRequest $request, IncidentReport $incidentReport)
    {
        $incidentReport->update($request->all());
        return response()->json(['message' => 'incidentReport updated successfully'], 200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IncidentReport $incidentReport)
    {
        $incidentReport->delete();
        return response()->json(['message' => 'incidentReport deleted successfully'], 200);
    }

    public function getTotalIncidentReport(IncidentReport $incidentReport)
    {
        $incidentReport = IncidentReport::count();
        return response()->json(['total' => $incidentReport], 200);
    }

    public function uploadIncidentFile(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'incidentId' => 'required|exists:incident_reports,id'
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('incident_files', $fileName, 'public');

            $incidentFile = IncidentFile::create([
                'incident_report_id' => $request->incidentId,
                'path' => Storage::url($filePath),
                'name' => $fileName
            ]);

            return response()->json($incidentFile, 201);
        }

        return response()->json(['error' => 'File upload failed'], 400);
    }



}