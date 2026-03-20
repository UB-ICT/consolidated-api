<?php

namespace Modules\PublicSafety\Database\Seeders;

use Modules\PublicSafety\Models\IncidentStatus;
use Illuminate\Database\Seeder;

class IncidentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        IncidentStatus::create(['statuses' => 'Open']);
        IncidentStatus::create(['statuses' => 'Closed']);
        IncidentStatus::create(['statuses' => 'Pending']);
    }
}
