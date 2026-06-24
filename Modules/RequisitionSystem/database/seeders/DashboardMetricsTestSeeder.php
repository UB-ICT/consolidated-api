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
        $requesterRoleId  = $db->table('public.roles')->where('role_name', 'requester')->value('id');
        $directorRoleId   = $db->table('public.roles')->where('role_name', 'director-dean')->value('id');
        $budgetRoleId     = $db->table('public.roles')->where('role_name', 'budget-officer')->value('id');

        // Dynamic lookup from the database to prevent foreign key errors
        $statusIds = [
            'Draft'        => $db->table('purchase_order_requisition.statuses')->where('name', 'Draft')->value('id') ?? 1,
            'Pending'      => $db->table('purchase_order_requisition.statuses')->where('name', 'Pending')->value('id') ?? 2,
            'Approved'     => $db->table('purchase_order_requisition.statuses')->where('name', 'Approved')->value('id') ?? 3,
            'Rejected'     => $db->table('purchase_order_requisition.statuses')->where('name', 'Rejected')->value('id') ?? 4,
            'Under Review' => $db->table('purchase_order_requisition.statuses')->where('name', 'Under Review')->value('id')
                ?? $db->table('purchase_order_requisition.statuses')->where('name', 'Pending')->value('id') // Safe fallback to Pending if ID 5 doesn't exist
                ?? 2,
        ];

        // Fetch Cost Center IDs dynamically using your exact table values
        $admissionsCcId = $db->table('purchase_order_requisition.cost_centers')->where('name', 'Admissions')->value('id') ?? 1;
        $hrCcId         = $db->table('purchase_order_requisition.cost_centers')->where('name', 'Human Resources')->value('id') ?? 12;
        $toledoCcId     = $db->table('purchase_order_requisition.cost_centers')->where('name', 'Toledo')->value('id') ?? 30;

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

        $user->roles()->detach();
        $user->costCenters()->detach();

        if ($directorRoleId) $user->roles()->attach($directorRoleId);
        if ($budgetRoleId)   $user->roles()->attach($budgetRoleId);
        if ($requesterRoleId) $user->roles()->attach($requesterRoleId);

        $user->costCenters()->attach($admissionsCcId);

        // 3. Clean up old test data
        Requisition::whereIn('cost_center_id', [$admissionsCcId, $hrCcId, $toledoCcId])->delete();

        // 🧪 4. GENERATING MOCK DATA MATRIX FOR WORKFLOW TESTING

        // --- Admissions ---
        $this->createMockRequisitions($admissionsCcId, $statusIds['Draft'], $currencyId, 1, 2);
        $this->createMockRequisitions($admissionsCcId, $statusIds['Pending'], $currencyId, 2, 1);
        $this->createMockRequisitions($admissionsCcId, $statusIds['Approved'], $currencyId, 5, 2);
        $this->createMockRequisitions($admissionsCcId, $statusIds['Under Review'], $currencyId, 2, 2);

        // --- HR & Toledo ---
        $this->createMockRequisitions($hrCcId, $statusIds['Pending'], $currencyId, 3, 2);
        $this->createMockRequisitions($toledoCcId, $statusIds['Pending'], $currencyId, 3, 1);
        $this->createMockRequisitions($hrCcId, $statusIds['Pending'], $currencyId, 4, 2);

        $this->command->info('Tri-role dashboard testing data cleanly seeded with dynamic status guardrails!');
    }

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
                'total' => rand(150, 4500),
                'priority' => 'normal',
                'date_prepared' => now(),
                'is_recurring' => false,
            ]);
        }
    }
}
