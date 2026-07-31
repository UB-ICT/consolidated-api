<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\ChartOfAccount;
use Modules\RequisitionSystem\Models\Item;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $account = ChartOfAccount::query()->firstOrCreate(
            ['account_no' => '70314'],
            ['description' => 'Computer Supplies']
        );

        Item::create([
            'chart_of_account_id' => $account->id,
            'quantity' => 2,
            'unit_cost' => 50,
            'total' => 100,
            'comments' => 'Ergonomic chairs',
            'requisition_id' => 1,
        ]);
    }
}
