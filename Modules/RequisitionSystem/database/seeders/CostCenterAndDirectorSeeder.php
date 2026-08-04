<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Auth\Models\Role;
use Modules\RequisitionSystem\Models\CostCenter;

class CostCenterAndDirectorSeeder extends Seeder
{
    public function run(): void
    {
        // Synced from current DB directors; optional `roles` overrides the
        // default director-dean assignment when the live data differs.
        $matrix = [
            ['cc' => 'Admissions', 'number' => '001', 'name' => 'Saddith Torres', 'email' => 'saddith.torres@ub.edu.bz'],
            ['cc' => 'Belize Policy Research Institute', 'number' => '002', 'name' => 'Dylan Vernon', 'email' => 'dvernon@ub.edu.bz'],
            ['cc' => 'Board of Trustees', 'number' => '003', 'name' => 'Vincent Palacio', 'email' => 'vpalacio@ub.edu.bz', 'roles' => ['president']],
            ['cc' => 'Budget and Finance', 'number' => '004', 'name' => 'Irene Garcia', 'email' => 'igarcia@ub.edu.bz', 'roles' => ['director-dean', 'director-of-finance']],
            ['cc' => 'Calabash Caye Field Station', 'number' => '005', 'name' => 'Jake Snaddon', 'email' => 'jsnaddon@ub.edu.bz'],
            ['cc' => 'Central Farm', 'number' => '006', 'name' => 'Maximilliano Ortega', 'email' => 'mortega@ub.edu.bz'],
            ['cc' => 'Environmental Research Institute', 'number' => '007', 'name' => 'Jake Snaddon', 'email' => 'jsnaddon@ub.edu.bz'],
            ['cc' => 'Faculty of Education and Arts', 'number' => '008', 'name' => 'Thisbe Usher', 'email' => 'tusher@ub.edu.bz'],
            ['cc' => 'Faculty of Health Sciences', 'number' => '009', 'name' => 'Lydia Thurton', 'email' => 'lthurton@ub.edu.bz'],
            ['cc' => 'Faculty of Management and Social Sciences', 'number' => '010', 'name' => 'Bernard Watler', 'email' => 'bwatler@ub.edu.bz'],
            ['cc' => 'Faculty of Science and Technology', 'number' => '011', 'name' => 'Apolonio Aguilar', 'email' => 'aaguilar@ub.edu.bz'],
            ['cc' => 'Human Resources', 'number' => '012', 'name' => 'Gilroy Middleton Jr', 'email' => 'gilroy.middleton.jr@ub.edu.bz'],
            ['cc' => 'Information and Communication Technology', 'number' => '013', 'name' => 'Luis Herrera', 'email' => 'luis.herrera@ub.edu.bz', 'roles' => ['director-dean', 'super-admin']],
            ['cc' => 'Institute of Banking and Finance', 'number' => '014', 'name' => 'Derrick Conorqui', 'email' => 'dconorqui@ub.edu.bz'],
            // Present in DB as a cost center; director missing locally — keep for fresh seeds.
            ['cc' => 'Intercultural Indigenous Language Institute', 'number' => '015', 'name' => 'Delmer Tzib', 'email' => 'delmer.tzib@ub.edu.bz'],
            ['cc' => 'Institutional Advancement', 'number' => '016', 'name' => 'Egbert Irving', 'email' => 'egbert.irving@ub.edu.bz'],
            ['cc' => 'Library', 'number' => '017', 'name' => 'Trevelee Williams', 'email' => 'twilliams@ub.edu.bz'],
            ['cc' => 'Marketing and Communications', 'number' => '018', 'name' => 'Yma Casey', 'email' => 'yma.casey@ub.edu.bz'],
            ['cc' => 'Open and Distance Learning', 'number' => '019', 'name' => 'Freida Palma', 'email' => 'fpalma@ub.edu.bz'],
            ['cc' => 'Physical Education and Sports', 'number' => '020', 'name' => 'Martin Cuellar', 'email' => 'mcuellar@ub.edu.bz'],
            ['cc' => 'Physical Plant', 'number' => '021', 'name' => 'Francis Burns', 'email' => 'fburns@ub.edu.bz'],
            ['cc' => 'President', 'number' => '022', 'name' => 'Vincent Palacio', 'email' => 'vpalacio@ub.edu.bz', 'roles' => ['president']],
            ['cc' => 'Public Safety', 'number' => '023', 'name' => 'John Salam', 'email' => 'jsalam@ub.edu.bz'],
            ['cc' => 'Quality Assurance', 'number' => '024', 'name' => 'Tritia Stuart Gordon', 'email' => 'tgordon@ub.edu.bz'],
            ['cc' => 'Regional Language Center', 'number' => '025', 'name' => 'Lugie Cruz', 'email' => 'lcruz@ub.edu.bz'],
            ['cc' => 'Registrar', 'number' => '026', 'name' => 'Concepcion Castro', 'email' => 'concepcion.castro@ub.edu.bz'],
            ['cc' => 'Research', 'number' => '027', 'name' => 'Joyanne De Four-Babb', 'email' => 'jbabb@ub.edu.bz'],
            ['cc' => 'Student Affairs', 'number' => '028', 'name' => 'Martin Cuellar', 'email' => 'mcuellar@ub.edu.bz'],
            ['cc' => 'Support Services', 'number' => '029', 'name' => 'Francis Burns', 'email' => 'fburns@ub.edu.bz'],
            ['cc' => 'Toledo', 'number' => '030', 'name' => 'Roy Polonio', 'email' => 'rpolonio@ub.edu.bz'],
            ['cc' => 'UB Academy', 'number' => '031', 'name' => 'Thisbe Usher', 'email' => 'tusher@ub.edu.bz'],
            ['cc' => 'UB School of Medicine', 'number' => '032', 'name' => 'Lisa Johnson', 'email' => 'ljohnson@ub.edu.bz'],
            ['cc' => 'Vice President', 'number' => '033', 'name' => 'Sherlene Enriquez-Savery', 'email' => 'senriquez@ub.edu.bz'],
            ['cc' => 'Wellness', 'number' => '034', 'name' => 'Martin Cuellar', 'email' => 'mcuellar@ub.edu.bz'],
        ];

        // Cost centers that exist in DB without a director-dean matrix row.
        // Accounts staff are assigned in AccountsUsersSeeder.
        $extraCostCenters = [
            ['name' => 'Accounts', 'number' => 'ACC'],
        ];

        foreach ($extraCostCenters as $extra) {
            $this->upsertCostCenter($extra['name'], $extra['number']);
        }

        foreach ($matrix as $row) {
            // Users live on pgsql; pivots on porsql. Do not wrap both in one
            // default-connection transaction or FK checks cannot see new users.
            $userAccount = User::firstOrCreate(
                ['email' => $row['email']],
                ['name' => $row['name']]
            );

            if ($userAccount->name !== $row['name']) {
                $userAccount->update(['name' => $row['name']]);
            }

            $roleNames = $row['roles'] ?? ['director-dean'];

            foreach ($roleNames as $roleName) {
                $role = Role::firstOrCreate(
                    ['role_name' => $roleName],
                    ['id' => (string) Str::uuid(), 'description' => $roleName]
                );

                DB::connection('pgsql')->table('user_roles')->updateOrInsert([
                    'user_id' => $userAccount->id,
                    'role_id' => $role->id,
                ]);
            }

            $costCenter = $this->upsertCostCenter($row['cc'], $row['number']);

            DB::connection('porsql')->table('user_cost_center')->updateOrInsert(
                [
                    'user_id' => $userAccount->id,
                    'cost_center_id' => $costCenter->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Additional individual role/cost-center grants that don't fit the
        // one-director-per-cost-center pattern above.
        $additionalGrants = [
            [
                'name' => 'James Faber',
                'email' => 'james.faber@ub.edu.bz',
                'cc' => 'Information and Communication Technology',
                'roles' => ['requester', 'super-admin'],
            ],
            [
                'name' => 'Noemi Polanco',
                'email' => 'npolanco@ub.edu.bz',
                'cc' => 'Physical Plant',
                'roles' => ['requester'],
            ],
        ];

        foreach ($additionalGrants as $row) {
            $userAccount = User::firstOrCreate(
                ['email' => $row['email']],
                ['name' => $row['name']]
            );

            if ($userAccount->name !== $row['name']) {
                $userAccount->update(['name' => $row['name']]);
            }

            foreach ($row['roles'] as $roleName) {
                $role = Role::firstOrCreate(
                    ['role_name' => $roleName],
                    ['id' => (string) Str::uuid(), 'description' => $roleName]
                );

                DB::connection('pgsql')->table('user_roles')->updateOrInsert([
                    'user_id' => $userAccount->id,
                    'role_id' => $role->id,
                ]);
            }

            $costCenter = CostCenter::firstOrCreate(['name' => $row['cc']]);

            DB::connection('porsql')->table('user_cost_center')->updateOrInsert(
                [
                    'user_id' => $userAccount->id,
                    'cost_center_id' => $costCenter->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function upsertCostCenter(string $name, string $number): CostCenter
    {
        $costCenter = CostCenter::firstOrCreate(
            ['name' => $name],
            ['number' => $number]
        );

        if ($costCenter->number !== $number) {
            $costCenter->update(['number' => $number]);
        }

        return $costCenter;
    }
}
