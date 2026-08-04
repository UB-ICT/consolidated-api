<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\CostCenter;

class AccountsUsersSeeder extends Seeder
{
    /**
     * Accounts cost-center staff synced from current DB (merge on email).
     * Roles are assigned only by UserRoleSeeder.
     *
     * @var list<array{name: string, email: string}>
     */
    private const USERS = [
        ['name' => 'Yvonne Lin', 'email' => 'ylin@ub.edu.bz'],
        ['name' => 'Desorie Contrerras', 'email' => 'dcontreras@ub.edu.bz'],
        ['name' => 'Carlos Cocom', 'email' => 'ccocom@ub.edu.bz'],
        ['name' => 'Lisa Ramirez', 'email' => 'lramirez@ub.edu.bz'],
        ['name' => 'Gianne Lewis', 'email' => 'gianni.lewis@ub.edu.bz'],
        ['name' => 'Jose Lopez', 'email' => 'jose.lopez@ub.edu.bz'],
        ['name' => 'Shiffana Flowers', 'email' => 'shiffana.flowers@ub.edu.bz'],
        ['name' => 'Shajida Zuniga', 'email' => 'shajida.zuniga@ub.edu.bz'],
        ['name' => 'Mariot Simon', 'email' => 'msimon@ub.edu.bz'],
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
