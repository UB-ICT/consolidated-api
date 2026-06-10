<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Bank;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Bank::insert([
            ['name' => 'The Belize Bank'],
            ['name' => 'Atlantic Bank'],
            ['name' => 'Heritage Bank'],
            ['name' => 'Holy Redeemer Credit Union'],
        ]);
    }
}
