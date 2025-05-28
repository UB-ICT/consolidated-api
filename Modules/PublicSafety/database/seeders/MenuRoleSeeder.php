<?php

namespace Modules\PublicSafety\Database\Seeders;

use Modules\PublicSafety\Models\MenuRole;
use Illuminate\Database\Seeder;

class MenuRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        MenuRole::create([
            'menu_id' => 1,
            'role_id' => 1,
        ]);
    }
}
