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
        // Dataset containing both directors/deans and standard requesters
        $matrix = [
            ['cc' => 'Admissions', 'director-dean' => 'Saddith Torres', 'email' => 'saddith.torres@ub.edu.bz'],
            ['cc' => 'Belize Policy Research Institute', 'director-dean' => 'Dylan Vernon', 'email' => 'dvernon@ub.edu.bz'],
            ['cc' => 'Board of Trustees', 'director-dean' => 'Vincent Palacio', 'email' => 'vpalacio@ub.edu.bz'],
            ['cc' => 'Budget and Finance', 'director-dean' => 'Irene Garcia', 'email' => 'igarcia@ub.edu.bz'],
            ['cc' => 'Calabash Caye Field Station', 'director-dean' => 'Jake Snaddon', 'email' => 'jsnaddon@ub.edu.bz'],
            ['cc' => 'Central Farm', 'director-dean' => 'Maximilliano Ortega', 'email' => 'mortega@ub.edu.bz'],
            ['cc' => 'Environmental Research Institute', 'director-dean' => 'Jake Snaddon', 'email' => 'jsnaddon@ub.edu.bz'],
            ['cc' => 'Faculty of Education and Arts', 'director-dean' => 'Thisbe Usher', 'email' => 'tusher@ub.edu.bz'],
            ['cc' => 'Faculty of Health Sciences', 'director-dean' => 'Lydia Thurton', 'email' => 'lthurton@ub.edu.bz'],
            ['cc' => 'Faculty of Management and Social Sciences', 'director-dean' => 'Bernard Watler', 'email' => 'bwatler@ub.edu.bz'],
            ['cc' => 'Faculty of Science and Technology', 'director-dean' => 'Apolonio Aguilar', 'email' => 'aaguilar@ub.edu.bz'],
            ['cc' => 'Human Resources', 'director-dean' => 'Gilroy Middleton Jr', 'email' => 'gilroy.middleton.jr@ub.edu.bz'],
            ['cc' => 'Information and Communication Technology', 'director-dean' => 'Luis Herrera', 'email' => 'luis.herrera@ub.edu.bz'],
            ['cc' => 'Information and Communication Technology', 'requester' => 'James Faber', 'email' => 'james.faber@ub.edu.bz', 'password' => 'Kingjames_x2'], // Preserved custom password
            ['cc' => 'Institute of Banking and Finance', 'director-dean' => 'Derrick Conorqui', 'email' => 'dconorqui@ub.edu.bz'],
            ['cc' => 'Intercultural Indigenous Language Institute', 'director-dean' => 'Delmer Tzib', 'email' => 'delmer.tzib@ub.edu.bz'],
            ['cc' => 'Institutional Advancement', 'director-dean' => 'Egbert Irving', 'email' => 'egbert.irving@ub.edu.bz'],
            ['cc' => 'Library', 'director-dean' => 'Trevelee Williams', 'email' => 'twilliams@ub.edu.bz'],
            ['cc' => 'Marketing and Communications', 'director-dean' => 'Yma Casey', 'email' => 'yma.casey@ub.edu.bz'],
            ['cc' => 'Open and Distance Learning', 'director-dean' => 'Freida Palma', 'email' => 'fpalma@ub.edu.bz'],
            ['cc' => 'Physical Education and Sports', 'director-dean' => 'Martin Cuellar', 'email' => 'mcuellar@ub.edu.bz'],
            ['cc' => 'Physical Plant', 'director-dean' => 'Francis Burns', 'email' => 'fburns@ub.edu.bz'],
            ['cc' => 'President', 'director-dean' => 'Vincent Palacio', 'email' => 'vpalacio@ub.edu.bz'],
            ['cc' => 'Public Safety', 'director-dean' => 'John Salam', 'email' => 'jsalam@ub.edu.bz'],
            ['cc' => 'Quality Assurance', 'director-dean' => 'Tritia Stuart Gordon', 'email' => 'tgordon@ub.edu.bz'],
            ['cc' => 'Regional Language Center', 'director-dean' => 'Lugie Cruz', 'email' => 'lcruz@ub.edu.bz'],
            ['cc' => 'Registrar', 'director-dean' => 'Concepcion Castro', 'email' => 'concepcion.castro@ub.edu.bz'],
            ['cc' => 'Research', 'director-dean' => 'Joyanne De Four-Babb', 'email' => 'jbabb@ub.edu.bz'],
            ['cc' => 'Student Affairs', 'director-dean' => 'Martin Cuellar', 'email' => 'mcuellar@ub.edu.bz'],
            ['cc' => 'Support Services', 'director-dean' => 'Francis Burns', 'email' => 'fburns@ub.edu.bz'],
            ['cc' => 'Toledo', 'director-dean' => 'Roy Polonio', 'email' => 'rpolonio@ub.edu.bz'],
            ['cc' => 'UB Academy', 'director-dean' => 'Thisbe Usher', 'email' => 'tusher@ub.edu.bz'],
            ['cc' => 'UB School of Medicine', 'director-dean' => 'Lisa Johnson', 'email' => 'ljohnson@ub.edu.bz'],
            ['cc' => 'Vice President', 'director-dean' => 'Sherlene Enriquez-Savery', 'email' => 'senriquez@ub.edu.bz'],
            ['cc' => 'Wellness', 'director-dean' => 'Martin Cuellar', 'email' => 'mcuellar@ub.edu.bz'],

            // Added Workflow System Users (Assigned to Information and Communication Technology Cost Center)
            ['cc' => 'Information and Communication Technology', 'budget-officer' => 'Stephanie Windsor', 'email' => 'swindsor@ub.edu.bz'],
            ['cc' => 'Information and Communication Technology', 'vice-president' => 'Steve Castillo', 'email' => 'steve.castillo@ub.edu.bz'],
            ['cc' => 'Information and Communication Technology', 'director-of-finance' => 'Daren Brown', 'email' => 'dbrown@ub.edu.bz'],
            ['cc' => 'Information and Communication Technology', 'purchase-officer' => 'Justina Oh', 'email' => 'joh@ub.edu.bz'],
        ];

        // Ensure all application roles exist up-front using firstOrCreate
        $rolesMap = [
            'director-dean'       => Role::firstOrCreate(['role_name' => 'director-dean'], ['id' => (string) Str::uuid(), 'description' => 'Head of Cost Center / Dean of Faculty']),
            'requester'           => Role::firstOrCreate(['role_name' => 'requester'], ['id' => (string) Str::uuid(), 'description' => 'Standard Cost Center Requisition Submitter']),
            'budget-officer'      => Role::firstOrCreate(['role_name' => 'budget-officer'], ['id' => (string) Str::uuid(), 'description' => 'Budget Reviewing Officer']),
            'vice-president'      => Role::firstOrCreate(['role_name' => 'vice-president'], ['id' => (string) Str::uuid(), 'description' => 'Vice President Executive Approver']),
            'director-of-finance' => Role::firstOrCreate(['role_name' => 'director-of-finance'], ['id' => (string) Str::uuid(), 'description' => 'Director of Finance Final Reviewer']),
            'purchase-officer'    => Role::firstOrCreate(['role_name' => 'purchase-officer'], ['id' => (string) Str::uuid(), 'description' => 'Purchasing Department Process Officer']),
        ];

        foreach ($matrix as $row) {
            // Determine dynamic name, target role string, and fallback tracking
            $detectedRoleKey = null;
            $name = 'Unknown User';

            foreach (array_keys($rolesMap) as $roleKey) {
                if (isset($row[$roleKey])) {
                    $detectedRoleKey = $roleKey;
                    $name = $row[$roleKey];
                    break;
                }
            }

            // Fallback safety catch
            if (!$detectedRoleKey) {
                continue;
            }

            $assignedRoleId = $rolesMap[$detectedRoleKey]->id;
            $passwordStr = $row['password'] ?? 'password';

            // 1. Check if user already exists on 'pgsql' by email to preserve real UUIDs
            $userAccount = User::where('email', $row['email'])->first();

            // 2. Fallback to registration if account does not exist
            if (!$userAccount) {
                $userAccount = User::create([
                    'name'     => $name,
                    'email'    => $row['email'],
                    'password' => bcrypt($passwordStr),
                ]);
            }

            // Assign the dynamically verified operational role link
            DB::connection('pgsql')->table('user_roles')->updateOrInsert(
                [
                    'user_id' => $userAccount->id,
                    'role_id' => $assignedRoleId
                ]
            );

            $noCostCenterRoles = ['budget-officer', 'vice-president', 'director-of-finance', 'purchase-officer'];

            if (!in_array($detectedRoleKey, $noCostCenterRoles)) {
                $costCenter = CostCenter::firstOrCreate([
                    'name' => $row['cc']
                ]);

                DB::connection('porsql')->table('user_cost_center')->updateOrInsert(
                    [
                        'user_id'        => $userAccount->id,
                        'cost_center_id' => $costCenter->id
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }
        }
    }
}
