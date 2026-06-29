<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Models\Pipeline;

class StageSeeder extends Seeder
{
    public function run(): void
    {
        // 7. PIPELINE
        $pipeline = Pipeline::firstOrCreate(['name' => 'operations']);

        // 9. STAGES
        $draft = Stage::firstOrCreate(['name' => 'Draft']);
        $directorApproval = Stage::firstOrCreate(['name' => "Director's Approval"]);
        $stageBudgetOfficer = Stage::firstOrCreate(['name' => 'Budget Officer']);
        $vpApproval = Stage::firstOrCreate(['name' => 'VP Approval']);
        $financeApproval = Stage::firstOrCreate(['name' => 'Finance Approval']);
        $purchaseApproval = Stage::firstOrCreate(['name' => 'Purchase Officer Approval']);

        // Sync stages with sequence metadata inside the pivot table
        $pipeline->stages()->syncWithoutDetaching([
            $draft->id              => ['sequence' => 1],
            $directorApproval->id   => ['sequence' => 2],
            $stageBudgetOfficer->id => ['sequence' => 3],
            $vpApproval->id         => ['sequence' => 4],
            $financeApproval->id    => ['sequence' => 5],
            $purchaseApproval->id   => ['sequence' => 6]
        ]);
    }
}
