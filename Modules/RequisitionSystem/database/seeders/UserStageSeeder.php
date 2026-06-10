<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\UserStage;

class UserStageSeeder extends Seeder
{
    public function run(): void
    {
        UserStage::insert([
            [
                'user_id' => "cd4830a8-48f6-447b-96aa-cf0f279d8b63",
                'stage_id' => 2,
            ],
        ]);
    }
}
