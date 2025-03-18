<?php

namespace Database\Seeders;

use App\Models\IncidentReport;
use App\Models\IncidentFile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IncidentReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $incidentReport = IncidentReport::create([
            'report' => 'Description of incident 1',
            'disposition' => 'Disposition of incident 1',
            'case_number' => 'Case number for incident 1',
            'action' => 'Action taken for incident 1',
            'location' => 'Location of incident 1',
            'uploaded_by' => 'Uploader of incident 1',
            'frequency' => 1,
            'incident_reoccured' => now(),
            'incident_files' => './path/to/file1',
            'incident_status_id' => 1,
            'user_id' => 1,
            'campus_id' => 1,
            'building_id' => 1,
            'incident_type_id' => 'incident_type_1',
        ]);

        // Create associated incident files
        IncidentFile::create([
            'incident_report_id' => $incidentReport->id,
            'path' => 'storage/incident_files/file1.jpg',
            'name' => 'file1.jpg',
        ]);

        IncidentFile::create([
            'incident_report_id' => $incidentReport->id,
            'path' => 'storage/incident_files/file2.jpg',
            'name' => 'file2.jpg',
        ]);
    }
}