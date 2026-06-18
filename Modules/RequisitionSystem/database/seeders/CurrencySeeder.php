<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Currency;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['id' => 1, 'name' => 'BZD', 'symbol' => 'BZ$'],
            ['id' => 2, 'name' => 'USD', 'symbol' => '$'],
            ['id' => 3, 'name' => 'EUR', 'symbol' => '€'],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(['id' => $currency['id']], $currency);
        }
    }
}
