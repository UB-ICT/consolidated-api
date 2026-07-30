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
            // Foreign banks used by overseas vendors in the vendor master sheet.
            ['name' => 'Wells Fargo Bank, N.A.'],
            ['name' => 'JPMorgan Chase Bank, N.A.'],
            ['name' => 'Bank of America'],
            ['name' => 'BMO Harris Bank'],
            ['name' => 'National Commercial Bank (Jamaica) Limited'],
            ['name' => 'Citibank, N.A.'],
            ['name' => 'BAC International Bank'],
            ['name' => 'Banco Promerica'],
        ];

        foreach ($banks as $bank) {
            Bank::updateOrCreate(['name' => $bank['name']], $bank);
        }
    }
}
