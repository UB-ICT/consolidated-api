<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;
use Illuminate\Support\Facades\DB;

class DashboardMetricsTestSeeder extends Seeder
{
    public function run(): void
    {
        $db = DB::connection('porsql');

        // Find your Director-Dean role id
        $directorDeanRoleId = $db->table('public.roles')->where('role_name', 'director-dean')->value('id');

        $statusIds = [
            'Pending' => $db->table('purchase_order_requisition.statuses')->where('name', 'Pending')->value('id'),
            'Approved' => $db->table('purchase_order_requisition.statuses')->where('name', 'Approved')->value('id'),
            'Rejected' => $db->table('purchase_order_requisition.statuses')->where('name', 'Rejected')->value('id'),
            'Cost Center Review' => $db->table('purchase_order_requisition.statuses')->where('name', 'Cost Center Review')->value('id'),
        ];

        $admissionsCcId = $db->table('purchase_order_requisition.cost_centers')->where('name', 'Admissions')->value('id');
        $currencyId = $db->table('purchase_order_requisition.currencies')->where('name', 'BZD')->value('id') ?? 1;

        // Clean up user link and assign Director-Dean
        $user = User::updateOrCreate(
            ['email' => 'james.faber@ub.edu.bz'],
            ['name' => 'James Faber', 'password' => Hash::make('Kingjames_x2'), 'email_verified_at' => now()]
        );
        $user->roles()->detach();
        $user->costCenters()->detach();

        $user->roles()->attach($directorDeanRoleId);
        $user->costCenters()->attach($admissionsCcId);

        // Clear previous test requisitions to avoid inflation errors
        Requisition::where('cost_center_id', $admissionsCcId)->delete();

        // 🧪 SEEDING CONTROL GROUP VALUES (To match your exact interface image example):

        // Card 1: Awaiting My Action = 1 (Stage ID 2, Status Pending)
        $this->createMockItem($admissionsCcId, $statusIds['Pending'], $currencyId, 2, 1);

        // Card 2: In Pipeline = 4 (Total active across pipeline steps excluding final resolutions)
        // (The item at Stage 2 already counts as 1. Let's add 3 more at subsequent processing stages to get 4)
        $this->createMockItem($admissionsCcId, $statusIds['Pending'], $currencyId, 3, 2); // Budget Officer
        $this->createMockItem($admissionsCcId, $statusIds['Pending'], $currencyId, 4, 1); // VP Approval

        // Card 3: Approved This Month = 2 (Status Approved, updated_at defaults to current month)
        $this->createMockItem($admissionsCcId, $statusIds['Approved'], $currencyId, 5, 2);

        // Card 4: Supplier Requests = 2
        $this->createMockItem($admissionsCcId, $statusIds['Cost Center Review'], $currencyId, 2, 2);

        $this->command->info('Director-Dean dashboard testing layout successfully populated!');
    }

    private function createMockItem($costCenterId, $statusId, $currencyId, $stageId, $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            Requisition::create([
                'number' => 'REQ-' . date('Y') . '-' . Str::upper(Str::random(5)),
                'cost_center_id' => $costCenterId,
                'status_id' => $statusId,
                'currency_id' => $currencyId,
                'stage_id' => $stageId,
                'current_stage_sequence' => $stageId,
                'total' => rand(500, 1500),
                'priority' => 'normal',
                'date_prepared' => now(),
                'is_recurring' => false,
            ]);
        }
    }
}
