<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\CostCenter;

class AccountsUsersSeeder extends Seeder
{
    /**
     * Accounts cost-center staff synced from current DB (merge on email).
     *
     * @var list<array{name: string, email: string, role: string}>
     */
    private const USERS = [
        ['name' => 'Yvonne Lin', 'email' => 'ylin@ub.edu.bz', 'role' => 'budget-officer'],
        ['name' => 'Desorie Contrerras', 'email' => 'dcontreras@ub.edu.bz', 'role' => 'vice-president'],
        ['name' => 'Carlos Cocom', 'email' => 'ccocom@ub.edu.bz', 'role' => 'purchase-officer'],
        ['name' => 'Lisa Ramirez', 'email' => 'lramirez@ub.edu.bz', 'role' => 'budget-officer'],
        ['name' => 'Gianne Lewis', 'email' => 'gianni.lewis@ub.edu.bz', 'role' => 'requester'],
        ['name' => 'Jose Lopez', 'email' => 'jose.lopez@ub.edu.bz', 'role' => 'purchase-officer'],
        ['name' => 'Shiffana Flowers', 'email' => 'shiffana.flowers@ub.edu.bz', 'role' => 'requester'],
        ['name' => 'Shajida Zuniga', 'email' => 'shajida.zuniga@ub.edu.bz', 'role' => 'requester'],
        ['name' => 'Mariot Simon', 'email' => 'msimon@ub.edu.bz', 'role' => 'vice-president'],
    ];

    public function run(): void
    {
        $costCenter = CostCenter::firstOrCreate(
            ['name' => 'Accounts'],
            ['number' => 'ACC']
        );

        if ($costCenter->number !== 'ACC') {
            $costCenter->update(['number' => 'ACC']);
        }

        foreach (self::USERS as $row) {
            $user = User::firstOrCreate(
                ['email' => $row['email']],
                ['name' => $row['name']]
            );

            if ($user->name !== $row['name']) {
                $user->update(['name' => $row['name']]);
            }

            $role = Role::firstOrCreate(
                ['role_name' => $row['role']],
                [
                    'id' => (string) Str::uuid(),
                    'description' => $row['role'],
                ]
            );

            // Users/roles on pgsql; cost-center pivot on porsql.
            DB::connection('pgsql')->table('user_roles')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                ]
            );

            DB::connection('porsql')->table('user_cost_center')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'cost_center_id' => $costCenter->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
