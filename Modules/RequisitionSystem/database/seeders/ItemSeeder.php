<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Modules\RequisitionSystem\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        Item::insert([
            [
                'description' => 'Office Chairs',
                'quantity' => 2,
                'line_item_number' => 1,
                'unit_cost' => 50,
                'total' => 100,
                'comments' => 'Ergonomic chairs',
                'requisition_id' => 1,
            ],
        ]);
    }
}
