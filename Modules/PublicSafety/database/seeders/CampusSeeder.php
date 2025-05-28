<?php

namespace Modules\PublicSafety\Database\Seeders;

use Modules\PublicSafety\Models\Campus;
use Illuminate\Database\Seeder;

class CampusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Campus::create(['campus' => 'Business Campus',]);
        Campus::create(['campus' => 'FST Campus']);
        Campus::create(['campus' => 'IT Campus']);
        Campus::create(['campus' => 'Social Studies Campus']);

    }
}
