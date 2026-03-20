<?php

namespace Modules\PublicSafety\Database\Seeders;

use Modules\PublicSafety\Models\IncidentReport;
use Modules\PublicSafety\Models\IncidentFile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IncidentReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        IncidentReport::create([
            'report' => 'Description of incident 1',
            'description' => 'Detailed description',
            'disposition' => 'Disposition of incident 1',
            'case_number' => 'Case number for incident 1',
            'action' => 'Action taken for incident 1',
            'location' => 'Location of incident 1',
            'uploaded_by' => 'Uploader of incident 1',
            'frequency' => 1,
            'incident_reoccured' => '2025-04-30 22:13:28',
            'incident_status_id' => 1,
            'user_id' => 1,
            'campus_id' => 1,
            'building_id' => 1,
            'incident_type_id' => 1
        ]);

        // Create associated incident files
        IncidentFile::create([
            'incident_report_id' => 1, // Must match column name in DB
            'path' => 'storage/incident_files/file1.jpg',
            'name' => 'file1.jpg',
        ]);

    }
}