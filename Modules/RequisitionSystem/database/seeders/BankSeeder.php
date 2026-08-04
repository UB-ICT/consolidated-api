<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Bank;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        // Synced from live banks (excluding orphan alias "Belize Bank").
        $banks = [
            'Atlantic Bank Limited',
            'BAC International Bank',
            'Banco Promerica',
            'Bank of America',
            'BMO Harris Bank',
            'Citibank, N.A.',
            'Heritage Bank Limited',
            'JPMorgan Chase Bank, N.A.',
            'National Bank of Belize',
            'National Commercial Bank (Jamaica) Limited',
            'The Belize Bank Limited',
            'Wells Fargo Bank, N.A.',
        ];

        foreach ($banks as $name) {
            Bank::updateOrCreate(['name' => $name], ['name' => $name]);
        }
    }
}
