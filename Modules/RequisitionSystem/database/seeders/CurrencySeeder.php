<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Currency;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Currency::insert([
            ['name' => 'Belize Dollar', 'symbol' => 'BZ$'],
            ['name' => 'US Dollar', 'symbol' => '$'],
            ['name' => 'Euro', 'symbol' => '€'],
            ['name' => 'British Pound', 'symbol' => '£'],
        ]);
    }
}
