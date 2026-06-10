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
            // 1. USER
            // =========================
            $approver = User::firstOrCreate(
                ['email' => 'james.faber@ub.edu.bz'],
                [
                    'name' => 'James Faber',
                    'password' => bcrypt('Kingjames_x2')
                ]
            );

            // =========================
            // 2. COST CENTER
            // =========================
            $costCenter = CostCenter::firstOrCreate(
                ['name' => 'ICT-001'],
                ['type' => 'ICT']
            );

            // =========================
            // 3. COUNTRY
            // =========================
            $country = Country::firstOrCreate([
                'name' => 'Belize'
            ]);

            // =========================
            // 4. SUPPLIER (FIXED DUPLICATE ISSUE)
            // =========================
            $supplier = Supplier::firstOrCreate(
                ['email' => 'abc@supplier.com'],
                [
                    'name' => 'ABC Supplies Ltd',
                    'contact_person' => 'John Doe',
                    'phone_number' => '+5016000001',
                    'TIN' => 'TIN-001'
                ]
            );

            // =========================
            // 5. ADDRESS
            // =========================
            $address = Address::firstOrCreate(
                [
                    'supplier_id' => $supplier->id,
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
            // 9. STAGES
            // =========================
            $submitted = Stage::firstOrCreate([
                'name' => 'Submitted',
                'pipeline_id' => $pipeline->id
            ]);

            $directorApproval = Stage::firstOrCreate([
                'name' => "Director's Approval",
                'pipeline_id' => $pipeline->id
            ]);

            $budgetOfficer = Stage::firstOrCreate([
                'name' => 'Budget Officer',
                'pipeline_id' => $pipeline->id
            ]);

            $vpApproval = Stage::firstOrCreate([
                'name' => 'VP Approval',
                'pipeline_id' => $pipeline->id
            ]);

            $financeApproval = Stage::firstOrCreate([
                'name' => 'Finance Approval',
                'pipeline_id' => $pipeline->id
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
                'supplier_id' => $supplier->id,
                'bank_id' => $bank->id,
            ], [
                'account_number' => '1234567890',
                'account_name' => 'ABC Supplies Ltd',
                'address' => 'Belize City'
            ]);

            // =========================
            // 12. REQUISITION
            // =========================
            $requisition = Requisition::create([
                'number' => 'REQ-' . now()->format('Y') . '-0001',
                'cost_center_id' => $costCenter->id,
                'supplier_id' => $supplier->id,
                'status_id' => $status->id,
                'currency_id' => $currency->id,
                'conversion_rate_id' => $rate->id,
                'total' => 0,
                'stage_id' => $submitted->id,
                'date_prepared' => now(),
            ]);

            // =========================
            // 13. ITEMS
            // =========================
            $items = collect([
                [
                    'description' => 'Office Chairs',
                    'quantity' => 2,
                    'unit_cost' => 50,
                ],
                [
                    'description' => 'Office Desks',
                    'quantity' => 1,
                    'unit_cost' => 200,
                ],
            ])->map(function ($item, $index) use ($requisition) {
                return Item::create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_item_number' => $index + 1,
                    'total' => $item['quantity'] * $item['unit_cost'],
                    'requisition_id' => $requisition->id,
                ]);
            });

            $requisition->update([
                'total' => $items->sum('total')
            ]);

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
                'requisition_id' => $requisition->id,
                'user_id' => $approver->id,
                'stage_id' => $directorApproval->id,
            ], [
                'status' => 'pending'
            ]);
        });
    }
}
