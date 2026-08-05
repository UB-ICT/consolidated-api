<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;

/**
 * Sets up James Faber with workflow roles and Admissions cost center
 * for dashboard/layout testing. Does not create requisitions.
 */
class DashboardMetricsTestSeeder extends Seeder
{
    public function run(): void
    {
        $db = DB::connection('porsql');

        $roleIds = [
            'requester'           => $db->table('public.roles')->where('role_name', 'requester')->value('id'),
            'director-dean'       => $db->table('public.roles')->where('role_name', 'director-dean')->value('id'),
            'budget-officer'      => $db->table('public.roles')->where('role_name', 'budget-officer')->value('id'),
            'vice-president'      => $db->table('public.roles')->where('role_name', 'vice-president')->value('id'),
            'director-of-finance' => $db->table('public.roles')->where('role_name', 'director-of-finance')->value('id'),
            'purchase-officer'    => $db->table('public.roles')->where('role_name', 'purchase-officer')->value('id'),
        ];

        $admissionsCcId = $db->table('purchase_order_requisition.cost_centers')
            ->where('name', 'Admissions')
            ->value('id');

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

        foreach ($roleIds as $id) {
            if ($id) {
                $user->roles()->attach($id);
            }
        }

        if ($admissionsCcId) {
            $user->costCenters()->attach($admissionsCcId);
        }

        $this->command?->info('Dashboard test user seeded (no mock requisitions).');
    }
}
