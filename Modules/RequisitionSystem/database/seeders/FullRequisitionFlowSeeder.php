<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Auth\Models\Role;
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
    UserStage,
};

class FullRequisitionFlowSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // ==============================================================
            // 1. COST CENTERS (Type column removed)
            // ==============================================================
            $ictCC  = CostCenter::firstOrCreate(['name' => 'ICT-001']);
            $fstCC  = CostCenter::firstOrCreate(['name' => 'FST-002']);
            $fmssCC = CostCenter::firstOrCreate(['name' => 'FMSS-003']);
            $accCC  = CostCenter::firstOrCreate(['name' => 'ACC-004']);
            $medCC  = CostCenter::firstOrCreate(['name' => 'MED-005']);

            // ==============================================================
            // 2. USER
            // ==============================================================
            $approver = User::firstOrCreate(
                ['email' => 'james.faber@ub.edu.bz'],
                [
                    'name' => 'James Faber',
                    'password' => bcrypt('Kingjames_x2'),
                ]
            );

            // Link James Faber explicitly to ONLY 3 out of the 5 cost centers
            $assignedCostCenters = [$ictCC->id, $fstCC->id, $medCC->id];

            foreach ($assignedCostCenters as $ccId) {
                DB::connection('porsql')->table('user_cost_center')->updateOrInsert(
                    [
                        'user_id' => $approver->id,
                        'cost_center_id' => $ccId
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }

            // ==============================================================
            // 2b. ROLES CREATION & ASSIGNMENT
            // ==============================================================
            $budgetOfficerRole = Role::firstOrCreate(
                ['role_name' => 'Budget Officer'],
                ['id' => (string) Str::uuid(), 'description' => 'Global Budget Oversight']
            );
            $vpRole = Role::firstOrCreate(
                ['role_name' => 'VP'],
                ['id' => (string) Str::uuid(), 'description' => 'Vice President Approval Access']
            );
            $financeDirectorRole = Role::firstOrCreate(
                ['role_name' => 'Director of Finance'],
                ['id' => (string) Str::uuid(), 'description' => 'Finance Department Executive Management']
            );
            $payrollOfficerRole = Role::firstOrCreate(
                ['role_name' => 'Payroll Officer'],
                ['id' => (string) Str::uuid(), 'description' => 'Payroll Processing Controls']
            );
            $presidentRole = Role::firstOrCreate(
                ['role_name' => 'President'],
                ['id' => (string) Str::uuid(), 'description' => 'President of the Company']
            );
            $requesterRole = Role::firstOrCreate(
                ['role_name' => 'Requester'],
                ['id' => (string) Str::uuid(), 'description' => 'Standard Departmental Requisitioner']
            );

            // Assign James Faber to the restricted "Requester" role by default
            DB::connection('pgsql')->table('user_roles')->updateOrInsert(
                [
                    'user_id' => $approver->id,
                    'role_id' => $requesterRole->id
                ],
            );

            // ==============================================================
            // 3. COUNTRY
            // ==============================================================
            $country = Country::firstOrCreate([
                'name' => 'Belize'
            ]);

            // ==============================================================
            // 4. SUPPLIERS
            // ==============================================================
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

            // ==============================================================
            // 5. ADDRESS
            // ==============================================================
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

            // ==============================================================
            // 6. CURRENCY
            // ==============================================================
            $currency = Currency::firstOrCreate([
                'name' => 'Belize Dollar'
            ], [
                'symbol' => 'BZ$'
            ]);

            // ==============================================================
            // 7. CONVERSION RATE
            // ==============================================================
            $rate = ConversionRate::firstOrCreate([
                'currency_id' => $currency->id
            ], [
                'rate' => 1
            ]);

            // ==============================================================
            // 8. PIPELINE
            // ==============================================================
            $pipeline = Pipeline::firstOrCreate([
                'name' => 'operations'
            ]);

            // ==============================================================
            // 9. STAGES
            // ==============================================================
            $submitted = Stage::firstOrCreate(['name' => 'Submitted']);
            $directorApproval = Stage::firstOrCreate(['name' => "Director's Approval"]);
            $stageBudgetOfficer = Stage::firstOrCreate(['name' => 'Budget Officer']);
            $vpApproval = Stage::firstOrCreate(['name' => 'VP Approval']);
            $financeApproval = Stage::firstOrCreate(['name' => 'Finance Approval']);

            $pipeline->stages()->syncWithoutDetaching([
                $submitted->id,
                $directorApproval->id,
                $stageBudgetOfficer->id,
                $vpApproval->id,
                $financeApproval->id
            ]);

            // ==============================================================
            // 10. STATUS
            // ==============================================================
            $status = Status::firstOrCreate([
                'name' => 'Pending'
            ]);

            // ==============================================================
            // 11. BANK
            // ==============================================================
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

            // ==============================================================
            // 12. REQUISITIONS (Distributed Across Explicit Cost Centers)
            // ==============================================================

            // Requisition 1: Standard Single-Purchase Item (Non-Recurring)
            $requisition1 = Requisition::create([
                'number' => 'REQ-' . now()->format('Y') . '-0001',
                'cost_center_id' => $ictCC->id,
                'status_id' => $status->id,
                'currency_id' => $currency->id,
                'conversion_rate_id' => $rate->id,
                'total' => 0,
                'priority' => 'high',
                'expected_delivery_date' => now()->addDays(10)->format('Y-m-d'),
                'stage_id' => $submitted->id,
                'date_prepared' => now(),
                'is_recurring' => false,
                'reminder_date' => null,
                'expiration_date' => null,
            ]);

            // Requisition 2: Active Recurring Contract (Upcoming Alert Triggered in 5 Days)
            $requisition2 = Requisition::create([
                'number' => 'REQ-' . now()->format('Y') . '-0002',
                'cost_center_id' => $fstCC->id,
                'status_id' => $status->id,
                'currency_id' => $currency->id,
                'conversion_rate_id' => $rate->id,
                'total' => 0,
                'priority' => 'medium',
                'expected_delivery_date' => now()->addWeeks(3)->format('Y-m-d'),
                'stage_id' => $submitted->id,
                'date_prepared' => now()->subDays(3),
                'is_recurring' => true,
                'reminder_date' => now()->addDays(5)->format('Y-m-d'), // 🔥 Fits inside console alert scanning window
                'expiration_date' => now()->addDays(35)->format('Y-m-d'),
            ]);

            // Requisition 3: Active Recurring Contract (Alert Triggered Today)
            $requisition3 = Requisition::create([
                'number' => 'REQ-' . now()->format('Y') . '-0003',
                'cost_center_id' => $medCC->id,
                'status_id' => $status->id,
                'currency_id' => $currency->id,
                'conversion_rate_id' => $rate->id,
                'total' => 0,
                'priority' => 'low',
                'expected_delivery_date' => now()->addMonths(1)->format('Y-m-d'),
                'stage_id' => $submitted->id,
                'date_prepared' => now()->subDays(6),
                'is_recurring' => true,
                'reminder_date' => now()->format('Y-m-d'), // 🔥 Triggers alert loop instantly
                'expiration_date' => now()->addDays(30)->format('Y-m-d'),
            ]);

            // Requisition 4: Hidden/Standard Cost Center Item
            $requisition4 = Requisition::create([
                'number' => 'REQ-' . now()->format('Y') . '-0004',
                'cost_center_id' => $accCC->id,
                'status_id' => $status->id,
                'currency_id' => $currency->id,
                'conversion_rate_id' => $rate->id,
                'total' => 0,
                'priority' => 'high',
                'expected_delivery_date' => now()->addDays(14)->format('Y-m-d'),
                'stage_id' => $submitted->id,
                'date_prepared' => now(),
                'is_recurring' => false,
                'reminder_date' => null,
                'expiration_date' => null,
            ]);

            // ==============================================================
            // 12b. SOURCING MATRIX BINDING
            // ==============================================================
            $requisition1->suppliers()->sync([
                $supplierABC->id => ['is_recommended' => true, 'quoted_total' => 4500.00],
                $supplierXYZ->id => ['is_recommended' => false, 'quoted_total' => 4900.00]
            ]);

            $requisition2->suppliers()->sync([
                $supplierABC->id => ['is_recommended' => false, 'quoted_total' => 3200.00],
                $supplierXYZ->id => ['is_recommended' => true, 'quoted_total' => 2950.00]
            ]);

            $requisition3->suppliers()->sync([
                $supplierXYZ->id => ['is_recommended' => true, 'quoted_total' => 8400.00]
            ]);

            $requisition4->suppliers()->sync([
                $supplierABC->id => ['is_recommended' => true, 'quoted_total' => 1200.00]
            ]);

            // ==============================================================
            // 13. LINE ITEMS 
            // ==============================================================
            $ictItems = collect([
                ['description' => '27-inch 4K Development Monitors', 'quantity' => 5, 'unit_cost' => 350.00],
                ['description' => 'Mechanical Keyboards (Hot-swappable)', 'quantity' => 10, 'unit_cost' => 85.00],
                ['description' => 'Ergonomic Mesh Office Chairs', 'quantity' => 4, 'unit_cost' => 220.00],
                ['description' => 'Cat6e Shielded Ethernet Cables (100ft)', 'quantity' => 15, 'unit_cost' => 25.00],
                ['description' => '24-Port Gigabit Managed Network Switch', 'quantity' => 2, 'unit_cost' => 450.00],
                ['description' => '1TB NVMe M.2 Internal SSDs', 'quantity' => 8, 'unit_cost' => 110.00],
                ['description' => 'Uninterruptible Power Supply (UPS) 1500VA', 'quantity' => 3, 'unit_cost' => 180.00],
                ['description' => 'USB-C Dual Display Docking Stations', 'quantity' => 6, 'unit_cost' => 130.00],
                ['description' => 'External 4TB Rugged Backup Hard Drives', 'quantity' => 4, 'unit_cost' => 125.00],
                ['description' => 'Anti-Static Wrist Straps & Toolkit Combo', 'quantity' => 5, 'unit_cost' => 45.00],
            ])->map(function ($item, $index) use ($requisition1) {
                return Item::create([
                    'description'      => $item['description'],
                    'quantity'         => $item['quantity'],
                    'unit_cost'        => $item['unit_cost'],
                    'line_item_number' => $index + 1,
                    'total'            => $item['quantity'] * $item['unit_cost'],
                    'requisition_id'   => $requisition1->id,
                ]);
            });
            $requisition1->update(['total' => $ictItems->sum('total')]);

            $fstItems = collect([
                ['description' => 'Digital Binocular Compound Microscopes', 'quantity' => 2, 'unit_cost' => 650.00],
                ['description' => 'Borosilicate Glass Beakers Set (250ml/500ml)', 'quantity' => 12, 'unit_cost' => 15.00],
                ['description' => 'Graduated Measuring Cylinders (100ml)', 'quantity' => 10, 'unit_cost' => 18.00],
                ['description' => 'Adjustable Volume Micropipettes (100-1000µL)', 'quantity' => 5, 'unit_cost' => 140.00],
                ['description' => 'Disposable Nitrile Gloves (Boxes of 100)', 'quantity' => 25, 'unit_cost' => 22.00],
                ['description' => 'Magnetic Stirrer with Hotplate', 'quantity' => 3, 'unit_cost' => 210.00],
                ['description' => 'PH Desktop Calibration Meters', 'quantity' => 4, 'unit_cost' => 95.00],
                ['description' => 'Centrifuge Tubes Rack (50ml Capacity)', 'quantity' => 8, 'unit_cost' => 12.50],
                ['description' => 'Distilled Water Purifier Cartridges', 'quantity' => 6, 'unit_cost' => 65.00],
                ['description' => 'Laboratory Safety Goggles (Anti-Fog)', 'quantity' => 30, 'unit_cost' => 8.00],
            ])->map(function ($item, $index) use ($requisition2) {
                return Item::create([
                    'description'      => $item['description'],
                    'quantity'         => $item['quantity'],
                    'unit_cost'        => $item['unit_cost'],
                    'line_item_number' => $index + 1,
                    'total'            => $item['quantity'] * $item['unit_cost'],
                    'requisition_id'   => $requisition2->id,
                ]);
            });
            $requisition2->update(['total' => $fstItems->sum('total')]);

            $medItems = collect([
                ['description' => 'Automated External Defibrillator (AED)', 'quantity' => 1, 'unit_cost' => 1800.00],
                ['description' => 'Digital Blood Pressure Monitors', 'quantity' => 6, 'unit_cost' => 75.00],
                ['description' => 'Infrared Non-Contact Forehead Thermometers', 'quantity' => 10, 'unit_cost' => 45.00],
                ['description' => 'Medical Grade Stainless Steel Stethoscopes', 'quantity' => 8, 'unit_cost' => 120.00],
                ['description' => 'Mobile Emergency Crash Cart Trolley', 'quantity' => 2, 'unit_cost' => 550.00],
                ['description' => 'Sterile Gauze Bandages (Case of 200)', 'quantity' => 5, 'unit_cost' => 60.00],
                ['description' => 'Antiseptic Solution / Isopropyl Alcohol (Gallons)', 'quantity' => 12, 'unit_cost' => 35.00],
                ['description' => 'Adjustable Height IV Fluid Poles', 'quantity' => 4, 'unit_cost' => 90.00],
                ['description' => 'Anatomical Medical Training Models', 'quantity' => 2, 'unit_cost' => 320.00],
                ['description' => 'Disposable Syringes with Needles (Box of 500)', 'quantity' => 3, 'unit_cost' => 110.00],
            ])->map(function ($item, $index) use ($requisition3) {
                return Item::create([
                    'description'      => $item['description'],
                    'quantity'         => $item['quantity'],
                    'unit_cost'        => $item['unit_cost'],
                    'line_item_number' => $index + 1,
                    'total'            => $item['quantity'] * $item['unit_cost'],
                    'requisition_id'   => $requisition3->id,
                ]);
            });
            $requisition3->update(['total' => $medItems->sum('total')]);

            $accItems = collect([
                ['description' => 'Heavy Duty Cross-Cut Paper Shredder', 'quantity' => 1, 'unit_cost' => 450.00],
                ['description' => 'Desktop Financial Calculators', 'quantity' => 5, 'unit_cost' => 60.00],
                ['description' => 'A4 Thermal Receipt Paper Rolls (Pack of 50)', 'quantity' => 5, 'unit_cost' => 90.00],
            ])->map(function ($item, $index) use ($requisition4) {
                return Item::create([
                    'description'      => $item['description'],
                    'quantity'         => $item['quantity'],
                    'unit_cost'        => $item['unit_cost'],
                    'line_item_number' => $index + 1,
                    'total'            => $item['quantity'] * $item['unit_cost'],
                    'requisition_id'   => $requisition4->id,
                ]);
            });
            $requisition4->update(['total' => $accItems->sum('total')]);

            // ==============================================================
            // 14. USER STAGE
            // ==============================================================
            UserStage::firstOrCreate([
                'user_id' => $approver->id,
                'stage_id' => $directorApproval->id,
            ]);

            // ==============================================================
            // 15. APPROVAL
            // ==============================================================
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
