<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Country;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        Country::insert([
            ['name' => 'Belize'],
            ['name' => 'United States'],
            ['name' => 'Canada'],
            ['name' => 'Mexico'],
        ]);
    }
}
