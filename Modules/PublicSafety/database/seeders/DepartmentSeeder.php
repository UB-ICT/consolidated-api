<?php

namespace Modules\PublicSafety\Database\Seeders;

use Modules\PublicSafety\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Department::create(['departments' => 'ICT']);
        Department::create(['departments' => 'Quality Insurance']);
        Department::create(['departments' => 'Student Affairs']);
        Department::create(['departments' => 'Payroll']);

    }
}
