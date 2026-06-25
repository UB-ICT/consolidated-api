<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Pipeline;

class PipelineSeeder extends Seeder
{
    public function run(): void
    {
        Pipeline::insert([
            ['name' => 'operations'],
        ]);
    }
}
