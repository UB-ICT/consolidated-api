<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\CostCenter;
use Modules\RequisitionSystem\Models\Tag;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        // Synced from live tags.
        $admissions = CostCenter::firstOrCreate(
            ['name' => 'Admissions'],
            ['number' => '001']
        );

        Tag::firstOrCreate(
            [
                'name' => 'Lab equipment',
                'cost_center_id' => $admissions->id,
            ]
        );
    }
}
