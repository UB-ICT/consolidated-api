<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;

use Modules\RequisitionSystem\Models\SupplierBank;

class SupplierBankSeeder extends Seeder
{
    public function run(): void
    {
        SupplierBank::insert([
            [
                'supplier_id' => 1,
                'bank_id' => 1,
                'account_number' => '123456789',
                'account_name' => 'ABC Supplies Ltd',
                'address' => 'Belize City'
            ],
        ]);
    }
}
