<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;

use Modules\RequisitionSystem\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::insert([
            [
                'name' => 'ABC Supplies Ltd',
                'contact_person' => 'John Doe',
                'phone_number' => '+5016000001',
                'email' => 'abc@supplier.com',
                'TIN' => 'TIN-001'
            ],
        ]);
    }
}
