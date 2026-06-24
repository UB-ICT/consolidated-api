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
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $db = DB::connection('porsql');

        // 1. Fetch exact master data IDs from your roles table
        $roleIds = [
            'requester'           => $db->table('public.roles')->where('role_name', 'requester')->value('id'),
            'director-dean'       => $db->table('public.roles')->where('role_name', 'director-dean')->value('id'),
            'budget-officer'      => $db->table('public.roles')->where('role_name', 'budget-officer')->value('id'),
            'vice-president'      => $db->table('public.roles')->where('role_name', 'vice-president')->value('id'),
            'director-of-finance' => $db->table('public.roles')->where('role_name', 'director-of-finance')->value('id'),
            'purchase-officer'    => $db->table('public.roles')->where('role_name', 'purchase-officer')->value('id'),
        ];

        // Map Statuses dynamically with a fallback safety net
        $statusIds = [
            'Draft'        => $db->table('purchase_order_requisition.statuses')->where('name', 'Draft')->value('id') ?? 1,
            'Pending'      => $db->table('purchase_order_requisition.statuses')->where('name', 'Pending')->value('id') ?? 2,
            'Approved'     => $db->table('purchase_order_requisition.statuses')->where('name', 'Approved')->value('id') ?? 3,
            'Rejected'     => $db->table('purchase_order_requisition.statuses')->where('name', 'Rejected')->value('id') ?? 4,
            'Under Review' => $db->table('purchase_order_requisition.statuses')->where('name', 'Under Review')->value('id')
                ?? $db->table('purchase_order_requisition.statuses')->where('name', 'Pending')->value('id')
                ?? 2,
        ];

        // Fetch multiple target Cost Centers from your dynamic table keys
        $admissionsCcId = $db->table('purchase_order_requisition.cost_centers')->where('name', 'Admissions')->value('id') ?? 1;
        $hrCcId         = $db->table('purchase_order_requisition.cost_centers')->where('name', 'Human Resources')->value('id') ?? 12;
        $toledoCcId     = $db->table('purchase_order_requisition.cost_centers')->where('name', 'Toledo')->value('id') ?? 30;
        $wellnessCcId   = $db->table('purchase_order_requisition.cost_centers')->where('name', 'Wellness')->value('id') ?? 34;

        $currencyId     = $db->table('purchase_order_requisition.currencies')->where('name', 'BZD')->value('id') ?? 1;

        // 2. Setup your core Test User (James Faber)
        $user = User::updateOrCreate(
            ['email' => 'james.faber@ub.edu.bz'],
            [
                'name' => 'James Faber',
                'password' => Hash::make('Kingjames_x2'),
                'email_verified_at' => now(),
            ]
        );

        // Reset relationship pivots to prevent duplication bugs
        $user->roles()->detach();
        $user->costCenters()->detach();

        // Attach all corporate structural roles to James so he can impersonate any layout using '?role='
        foreach ($roleIds as $roleName => $id) {
            if ($id) $user->roles()->attach($id);
        }

        // Map James locally as the specific departmental head of Admissions
        $user->costCenters()->attach($admissionsCcId);

        // 3. Clear old test instances in target nodes to prevent overlapping counters
        Requisition::whereIn('cost_center_id', [$admissionsCcId, $hrCcId, $toledoCcId, $wellnessCcId])->delete();

        // 🧪 4. SEEDING STRATEGIC CONTROL PIPELINE DATA

        // --- 🏢 COST CENTER: ADMISSIONS (Local Sandbox) ---
        // Requester Data
        $this->createMockRequisitions($admissionsCcId, $statusIds['Draft'], $currencyId, 1, 2);

        // Stage 2 (Director-Dean) - Awaiting Action
        $this->createMockRequisitions($admissionsCcId, $statusIds['Pending'], $currencyId, 2, 1);

        // System Wide Global Data Metrics (Month Clearance Targets)
        $this->createMockRequisitions($admissionsCcId, $statusIds['Approved'], $currencyId, 6, 2); // Fully cleared at Stage 6
        $this->createMockRequisitions($admissionsCcId, $statusIds['Under Review'], $currencyId, 2, 2); // Under review tags

        // --- 🏦 COST CENTER: HUMAN RESOURCES (External Dept) ---
        // Stage 3 (Budget Officer) - Awaiting Action
        $this->createMockRequisitions($hrCcId, $statusIds['Pending'], $currencyId, 3, 3);

        // --- 🗺️ COST CENTER: TOLEDO (External Dept) ---
        // Stage 4 (Vice President) - Awaiting Action
        $this->createMockRequisitions($toledoCcId, $statusIds['Pending'], $currencyId, 4, 1);

        // Active pipeline item currently traversing downstream
        $this->createMockRequisitions($toledoCcId, $statusIds['Pending'], $currencyId, 4, 1);

        // --- 🏥 COST CENTER: WELLNESS (External Dept) ---
        // Stage 5 (Director of Finance) - Awaiting Action
        $this->createMockRequisitions($wellnessCcId, $statusIds['Pending'], $currencyId, 5, 2);

        // Stage 6 (Purchase Officer) - Awaiting Action
        $this->createMockRequisitions($wellnessCcId, $statusIds['Pending'], $currencyId, 6, 1);

        $this->command->info('Full 6-Stage structural workflow metrics dataset seeded successfully!');
    }

    /**
     * Helper to generate mock requisition rows safely.
     */
    private function createMockRequisitions($costCenterId, $statusId, $currencyId, $stageId, $count): void
    {
        if (!$statusId || !$costCenterId || !$stageId) return;

        for ($i = 0; $i < $count; $i++) {
            Requisition::create([
                'number' => 'REQ-' . date('Y') . '-' . Str::upper(Str::random(5)),
                'cost_center_id' => $costCenterId,
                'status_id' => $statusId,
                'currency_id' => $currencyId,
                'stage_id' => $stageId,
                'current_stage_sequence' => $stageId,
                'total' => rand(200, 5000),
                'priority' => 'normal',
                'date_prepared' => now(),
                'is_recurring' => false,
            ]);
        }
    }
}
