<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\ConversionRate;
use Modules\RequisitionSystem\Models\Currency;

class ConversionRateSeeder extends Seeder
{
    public function run(): void
    {
        // get existing currency OR create one safely
        $currency = Currency::firstOrCreate([
            'name' => 'Belize Dollar',
        ], [
            'symbol' => 'BZ$'
        ]);

        ConversionRate::create([
            'currency_id' => $currency->id,
            'rate' => 1
        ]);
    }
}
