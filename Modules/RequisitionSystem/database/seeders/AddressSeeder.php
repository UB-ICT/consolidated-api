<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Address;
use Modules\RequisitionSystem\Models\Supplier;
use Modules\RequisitionSystem\Models\Country;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create dependencies safely
        $supplier = Supplier::first();
        $country = Country::first();

        if (!$supplier || !$country) {
            throw new \Exception("Supplier or Country must exist before seeding addresses.");
        }

        Address::create([
            'supplier_id' => $supplier->id,
            'street'      => '22 Trio Street',
            'city'        => 'Belize City',
            'district'    => 'Belize District',
            'postal_code' => '0000',
            'country_id'  => $country->id,
        ]);
    }
}
