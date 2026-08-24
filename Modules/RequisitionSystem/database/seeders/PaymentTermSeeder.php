<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\PaymentTerm;

class PaymentTermSeeder extends Seeder
{
    public function run(): void
    {
        $paymentTerms = [
            '7 Days',
            '10 Days',
            '14 Days',
            '30 Days',
        ];

        foreach ($paymentTerms as $name) {
            PaymentTerm::updateOrCreate(['name' => $name], ['name' => $name]);
        }
    }
}
