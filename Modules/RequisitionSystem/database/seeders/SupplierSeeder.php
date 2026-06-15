<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Supplier;
use Modules\RequisitionSystem\Models\Bank;
use Modules\RequisitionSystem\Models\SupplierBank;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // SUPPLIER 1: ABC Supplies Ltd
        // ==========================================

        $supplier1 = Supplier::updateOrCreate(
            ['email' => 'abc@supplier.com'],
            [
                'name'           => 'ABC Supplies Ltd',
                'contact_person' => 'John Doe',
                'phone_number'   => '+501 600-0001',
                'TIN'            => 'TIN-001',
                'status_id'      => 2, // 'Pending'
            ]
        );

        $bank1 = Bank::where('name', 'Atlantic Bank Limited')->first();

        if ($bank1) {
            SupplierBank::updateOrCreate(
                ['supplier_id' => $supplier1->id],
                [
                    'bank_id'        => $bank1->id,
                    'account_number' => '100123456',
                    'account_name'   => 'ABC Supplies Ltd Operating Account',
                    'address'        => '123 Cleghorn Street, Belize City',
                ]
            );
        }

        // ==========================================
        // SUPPLIER 2: XYZ Diagnostics & Tools
        // ==========================================

        $supplier2 = Supplier::updateOrCreate(
            ['email' => 'info@xyzdiagnostics.bz'],
            [
                'name'           => 'XYZ Diagnostics & Tools',
                'contact_person' => 'Jane Smith',
                'phone_number'   => '+501 615-9988',
                'TIN'            => 'TIN-042',
                'status_id'      => 3, // 'Pending'
            ]
        );

        $bank2 = Bank::where('name', 'The Belize Bank Limited')->first();

        if ($bank2) {
            SupplierBank::updateOrCreate(
                ['supplier_id' => $supplier2->id],
                [
                    'bank_id'        => $bank2->id,
                    'account_number' => '2507891011',
                    'account_name'   => 'XYZ Diagnostics & Tools',
                    'address'        => '60 Albert Street, Belize City',
                ]
            );
        }
    }
}
