<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Bank;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        $banks = [
            ['name' => 'Atlantic Bank Limited'],
            ['name' => 'The Belize Bank Limited'],
            ['name' => 'Heritage Bank Limited'],
            ['name' => 'National Bank of Belize'],
        ];

        foreach ($banks as $bank) {
            Bank::updateOrCreate(['name' => $bank['name']], $bank);
        }
    }
}
