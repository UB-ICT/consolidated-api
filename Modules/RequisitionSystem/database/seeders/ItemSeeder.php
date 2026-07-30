<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Modules\RequisitionSystem\Models\ChartOfAccount;
use Modules\RequisitionSystem\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $officeSuppliesId = ChartOfAccount::where('account_no', '70301')->value('id');

        Item::create([
            'quantity' => 2,
            'chart_of_account_id' => $officeSuppliesId,
            'unit_cost' => 50,
            'total' => 100,
            'comments' => 'Ergonomic chairs',
            'requisition_id' => 1,
        ]);
    }
}
