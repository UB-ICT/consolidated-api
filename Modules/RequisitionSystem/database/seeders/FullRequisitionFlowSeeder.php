<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\{
    CostCenter,
    Status,
    Pipeline,
    Stage,
    Currency,
    ConversionRate,
    Supplier,
    Bank,
    SupplierBank,
    Country,
    Address,
    Requisition,
    Item,
    Approval,
    UserStage
};

class FullRequisitionFlowSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // =========================
            // 1. COST CENTER 
            // =========================
            $costCenter = CostCenter::firstOrCreate(
                ['name' => 'ICT-001'],
                ['type' => 'ICT']
            );

            // =========================
            // 2. USER
            // =========================
            $approver = User::firstOrCreate(
                ['email' => 'james.faber@ub.edu.bz'],
                [
                    'name' => 'James Faber',
                    'password' => bcrypt('Kingjames_x2'),
                ]
            );

            // Seed the pivot relationship using DB facade to support cross-connection queries
            DB::connection('porsql')->table('user_cost_center')->updateOrInsert(
                [
                    'user_id' => $approver->id,
                    'cost_center_id' => $costCenter->id
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            // =========================
            // 3. COUNTRY
            // =========================
            $country = Country::firstOrCreate([
                'name' => 'Belize'
            ]);

            // =========================
            // 4. SUPPLIERS
            // =========================
            $supplierABC = Supplier::firstOrCreate(
                ['email' => 'abc@supplier.com'],
                [
                    'name' => 'ABC Supplies Ltd',
                    'contact_person' => 'John Doe',
                    'phone_number' => '+5016000001',
                    'TIN' => 'TIN-001'
                ]
            );

            $supplierXYZ = Supplier::firstOrCreate(
                ['email' => 'info@xyzdiagnostics.bz'],
                [
                    'name' => 'XYZ Diagnostics & Tools',
                    'contact_person' => 'Jane Smith',
                    'phone_number' => '+5016159988',
                    'TIN' => 'TIN-042'
                ]
            );

            // =========================
            // 5. ADDRESS
            // =========================
            Address::firstOrCreate(
                [
                    'supplier_id' => $supplierABC->id,
                    'street' => '22 Trio Street'
                ],
                [
                    'city' => 'Belize City',
                    'district' => 'Belize District',
                    'postal_code' => '0000',
                    'country_id' => $country->id,
                ]
            );

            // =========================
            // 6. CURRENCY
            // =========================
            $currency = Currency::firstOrCreate([
                'name' => 'Belize Dollar'
            ], [
                'symbol' => 'BZ$'
            ]);

            // =========================
            // 7. CONVERSION RATE
            // =========================
            $rate = ConversionRate::firstOrCreate([
                'currency_id' => $currency->id
            ], [
                'rate' => 1
            ]);

            // =========================
            // 8. PIPELINE
            // =========================
            $pipeline = Pipeline::firstOrCreate([
                'name' => 'operations'
            ]);

            // =========================
            // 9. STAGES (Updated for Many-to-Many Pivot Mapping Layout)
            // =========================
            $submitted = Stage::firstOrCreate(['name' => 'Submitted']);
            $directorApproval = Stage::firstOrCreate(['name' => "Director's Approval"]);
            $budgetOfficer = Stage::firstOrCreate(['name' => 'Budget Officer']);
            $vpApproval = Stage::firstOrCreate(['name' => 'VP Approval']);
            $financeApproval = Stage::firstOrCreate(['name' => 'Finance Approval']);

            // Sync structural links to the pipeline record seamlessly 
            $pipeline->stages()->syncWithoutDetaching([
                $submitted->id,
                $directorApproval->id,
                $budgetOfficer->id,
                $vpApproval->id,
                $financeApproval->id
            ]);

            // =========================
            // 10. STATUS
            // =========================
            $status = Status::firstOrCreate([
                'name' => 'Pending'
            ]);

            // =========================
            // 11. BANK
            // =========================
            $bank = Bank::firstOrCreate([
                'name' => 'Belize Bank'
            ]);

            SupplierBank::firstOrCreate([
                'supplier_id' => $supplierABC->id,
                'bank_id' => $bank->id,
            ], [
                'account_number' => '1234567890',
                'account_name' => 'ABC Supplies Ltd',
                'address' => 'Belize City'
            ]);

            // =========================
            // 12. REQUISITIONS
            // =========================

            // Requisition 1: Office Supplies
            $requisition1 = Requisition::create([
                'number' => 'REQ-' . now()->format('Y') . '-0001',
                'cost_center_id' => $costCenter->id,
                'status_id' => $status->id,
                'currency_id' => $currency->id,
                'conversion_rate_id' => $rate->id,
                'total' => 0,
                'stage_id' => $submitted->id,
                'date_prepared' => now(),
            ]);

            // Requisition 2: Developer Hardware Assets
            $requisition2 = Requisition::create([
                'number' => 'REQ-' . now()->format('Y') . '-0002',
                'cost_center_id' => $costCenter->id,
                'status_id' => $status->id,
                'currency_id' => $currency->id,
                'conversion_rate_id' => $rate->id,
                'total' => 0,
                'stage_id' => $submitted->id,
                'date_prepared' => now()->subDays(2),
            ]);

            // Requisition 3: Cloud Infrastructure Maintenance
            $requisition3 = Requisition::create([
                'number' => 'REQ-' . now()->format('Y') . '-0003',
                'cost_center_id' => $costCenter->id,
                'status_id' => $status->id,
                'currency_id' => $currency->id,
                'conversion_rate_id' => $rate->id,
                'total' => 0,
                'stage_id' => $submitted->id,
                'date_prepared' => now()->subDays(5),
            ]);

            // ==============================================================
            // 12b. SOURCING MATRIX BINDING (Populating the Pivot Table)
            // ==============================================================
            $requisition1->suppliers()->sync([
                $supplierABC->id => ['is_recommended' => true, 'quoted_total' => 300.00],
                $supplierXYZ->id => ['is_recommended' => false, 'quoted_total' => 350.00]
            ]);

            $requisition2->suppliers()->sync([
                $supplierABC->id => ['is_recommended' => false, 'quoted_total' => 1600.00],
                $supplierXYZ->id => ['is_recommended' => true, 'quoted_total' => 1500.00]
            ]);

            $requisition3->suppliers()->sync([
                $supplierABC->id => ['is_recommended' => true, 'quoted_total' => 1200.00]
            ]);

            // =========================
            // 13. ITEMS FOR ALL REQUISITIONS
            // =========================

            // Items for Requisition 1
            $items1 = collect([
                ['description' => 'Office Chairs', 'quantity' => 2, 'unit_cost' => 50],
                ['description' => 'Office Desks', 'quantity' => 1, 'unit_cost' => 200],
            ])->map(function ($item, $index) use ($requisition1) {
                return Item::create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_item_number' => $index + 1,
                    'total' => $item['quantity'] * $item['unit_cost'],
                    'requisition_id' => $requisition1->id,
                ]);
            });
            $requisition1->update(['total' => $items1->sum('total')]);

            // Items for Requisition 2
            $items2 = collect([
                ['description' => '27-inch 4K Monitors', 'quantity' => 3, 'unit_cost' => 350],
                ['description' => 'Mechanical Keyboards', 'quantity' => 5, 'unit_cost' => 90],
            ])->map(function ($item, $index) use ($requisition2) {
                return Item::create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_item_number' => $index + 1,
                    'total' => $item['quantity'] * $item['unit_cost'],
                    'requisition_id' => $requisition2->id,
                ]);
            });
            $requisition2->update(['total' => $items2->sum('total')]);

            // Items for Requisition 3
            $items3 = collect([
                ['description' => 'Annual Server Hosting & SSL renewal', 'quantity' => 1, 'unit_cost' => 1200],
            ])->map(function ($item, $index) use ($requisition3) {
                return Item::create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_item_number' => $index + 1,
                    'total' => $item['quantity'] * $item['unit_cost'],
                    'requisition_id' => $requisition3->id,
                ]);
            });
            $requisition3->update(['total' => $items3->sum('total')]);

            // =========================
            // 14. USER STAGE
            // =========================
            UserStage::firstOrCreate([
                'user_id' => $approver->id,
                'stage_id' => $directorApproval->id,
            ]);

            // =========================
            // 15. APPROVAL
            // =========================
            Approval::firstOrCreate([
                'requisition_id' => $requisition1->id,
                'user_id' => $approver->id,
                'stage_id' => $directorApproval->id,
            ], [
                'status' => 'pending'
            ]);
        });
    }
}
