<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;

/**
 * user_roles pivot synced from the live database (email + role_name).
 * Ensures each user exists, then attaches only the roles present in production.
 */
class UserRoleSeeder extends Seeder
{
    /**
     * @var list<array{email: string, name: string, role: string}>
     */
    private const ASSIGNMENTS = [
        ['email' => 'aaguilar@ub.edu.bz', 'name' => 'Apolonio Aguilar', 'role' => 'director-dean'],
        ['email' => 'bwatler@ub.edu.bz', 'name' => 'Bernard Watler', 'role' => 'director-dean'],
        ['email' => 'ccocom@ub.edu.bz', 'name' => 'Carlos Cocom', 'role' => 'purchase-officer'],
        ['email' => 'concepcion.castro@ub.edu.bz', 'name' => 'Concepcion Castro', 'role' => 'director-dean'],
        ['email' => 'dconorqui@ub.edu.bz', 'name' => 'Derrick Conorqui', 'role' => 'director-dean'],
        ['email' => 'dcontreras@ub.edu.bz', 'name' => 'Desorie Contrerras', 'role' => 'vice-president'],
        ['email' => 'delmer.tzib@ub.edu.bz', 'name' => 'Delmer Tzib', 'role' => 'director-dean'],
        ['email' => 'dvernon@ub.edu.bz', 'name' => 'Dylan Vernon', 'role' => 'director-dean'],
        ['email' => 'egbert.irving@ub.edu.bz', 'name' => 'Egbert Irving', 'role' => 'director-dean'],
        ['email' => 'fburns@ub.edu.bz', 'name' => 'Francis Burns', 'role' => 'director-dean'],
        ['email' => 'fpalma@ub.edu.bz', 'name' => 'Freida Palma', 'role' => 'director-dean'],
        ['email' => 'gianni.lewis@ub.edu.bz', 'name' => 'Gianne Lewis', 'role' => 'requester'],
        ['email' => 'gilroy.middleton.jr@ub.edu.bz', 'name' => 'Gilroy Middleton Jr', 'role' => 'director-dean'],
        ['email' => 'igarcia@ub.edu.bz', 'name' => 'Irene Garcia', 'role' => 'director-dean'],
        ['email' => 'igarcia@ub.edu.bz', 'name' => 'Irene Garcia', 'role' => 'director-of-finance'],
        ['email' => 'james.faber@ub.edu.bz', 'name' => 'James Faber', 'role' => 'requester'],
        ['email' => 'james.faber@ub.edu.bz', 'name' => 'James Faber', 'role' => 'super-admin'],
        ['email' => 'jbabb@ub.edu.bz', 'name' => 'Joyanne De Four-Babb', 'role' => 'director-dean'],
        ['email' => 'jose.lopez@ub.edu.bz', 'name' => 'Jose Lopez', 'role' => 'purchase-officer'],
        ['email' => 'jsalam@ub.edu.bz', 'name' => 'John Salam', 'role' => 'director-dean'],
        ['email' => 'jsnaddon@ub.edu.bz', 'name' => 'Jake Snaddon', 'role' => 'director-dean'],
        ['email' => 'lcruz@ub.edu.bz', 'name' => 'Lugie Cruz', 'role' => 'director-dean'],
        ['email' => 'ljohnson@ub.edu.bz', 'name' => 'Lisa Johnson', 'role' => 'director-dean'],
        ['email' => 'lramirez@ub.edu.bz', 'name' => 'Lisa Ramirez', 'role' => 'budget-officer'],
        ['email' => 'lthurton@ub.edu.bz', 'name' => 'Lydia Thurton', 'role' => 'director-dean'],
        ['email' => 'luis.herrera@ub.edu.bz', 'name' => 'Luis Herrera', 'role' => 'director-dean'],
        ['email' => 'luis.herrera@ub.edu.bz', 'name' => 'Luis Herrera', 'role' => 'super-admin'],
        ['email' => 'mcuellar@ub.edu.bz', 'name' => 'Martin Cuellar', 'role' => 'director-dean'],
        ['email' => 'mortega@ub.edu.bz', 'name' => 'Maximilliano Ortega', 'role' => 'director-dean'],
        ['email' => 'msimon@ub.edu.bz', 'name' => 'Mariot Simon', 'role' => 'vice-president'],
        ['email' => 'npolanco@ub.edu.bz', 'name' => 'Noemi Polanco', 'role' => 'requester'],
        ['email' => 'rpolonio@ub.edu.bz', 'name' => 'Roy Polonio', 'role' => 'director-dean'],
        ['email' => 'saddith.torres@ub.edu.bz', 'name' => 'Saddith Torres', 'role' => 'director-dean'],
        ['email' => 'senriquez@ub.edu.bz', 'name' => 'Sherlene Enriquez-Savery', 'role' => 'director-dean'],
        ['email' => 'shajida.zuniga@ub.edu.bz', 'name' => 'Shajida Zuniga', 'role' => 'requester'],
        ['email' => 'shiffana.flowers@ub.edu.bz', 'name' => 'Shiffana Flowers', 'role' => 'requester'],
        ['email' => 'tgordon@ub.edu.bz', 'name' => 'Tritia Stuart Gordon', 'role' => 'director-dean'],
        ['email' => 'tusher@ub.edu.bz', 'name' => 'Thisbe Usher', 'role' => 'director-dean'],
        ['email' => 'twilliams@ub.edu.bz', 'name' => 'Trevelee Williams', 'role' => 'director-dean'],
        ['email' => 'vpalacio@ub.edu.bz', 'name' => 'Vincent Palacio', 'role' => 'president'],
        ['email' => 'ylin@ub.edu.bz', 'name' => 'Yvonne Lin', 'role' => 'budget-officer'],
        ['email' => 'yma.casey@ub.edu.bz', 'name' => 'Yma Casey', 'role' => 'director-dean'],
    ];

    public function run(): void
    {
        $roleIds = Role::query()
            ->pluck('id', 'role_name');

        foreach (self::ASSIGNMENTS as $row) {
            $roleId = $roleIds[$row['role']] ?? null;
            if ($roleId === null) {
                $this->command?->warn("Skipping user_role: unknown role {$row['role']}");
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $row['email']],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $row['name'],
                    'password' => Hash::make('password'),
                ]
            );

            if ($user->name !== $row['name']) {
                $user->update(['name' => $row['name']]);
            }

            DB::connection('pgsql')->table('user_roles')->updateOrInsert([
                'user_id' => $user->id,
                'role_id' => $roleId,
            ]);
        }
    }
}
