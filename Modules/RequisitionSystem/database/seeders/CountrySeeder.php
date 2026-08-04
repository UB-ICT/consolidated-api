<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Country;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Belize', 'United States', 'Canada', 'Mexico'] as $name) {
            Country::firstOrCreate(['name' => $name]);
        }
    }
}
