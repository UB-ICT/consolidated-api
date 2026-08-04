<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Auth\Models\Role;

/**
 * Roles synced from the live database — only these eight exist in production.
 */
class RoleSeeder extends Seeder
{
    /**
     * @var list<array{role_name: string, description: string}>
     */
    private const ROLES = [
        ['role_name' => 'budget-officer', 'description' => 'System access role for Budget-officer'],
        ['role_name' => 'director-dean', 'description' => 'System access role for Director-dean'],
        ['role_name' => 'director-of-finance', 'description' => 'System access role for Director-of-finance'],
        ['role_name' => 'president', 'description' => 'System access role for President'],
        ['role_name' => 'purchase-officer', 'description' => 'System access role for Purchase-officer'],
        ['role_name' => 'requester', 'description' => 'System access role for Requester'],
        ['role_name' => 'super-admin', 'description' => 'System access role for Super-admin'],
        ['role_name' => 'vice-president', 'description' => 'System access role for Vice-president'],
    ];

    public function run(): void
    {
        foreach (self::ROLES as $row) {
            $role = Role::firstOrNew(['role_name' => $row['role_name']]);

            if (!$role->exists) {
                $role->id = (string) Str::uuid();
            }

            $role->description = $row['description'];
            $role->save();
        }
    }
}
