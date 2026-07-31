<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\RequisitionSystem\Models\Budget;
use Modules\RequisitionSystem\Models\BudgetLineItem;
use Modules\RequisitionSystem\Models\BudgetYear;
use Modules\RequisitionSystem\Models\ChartOfAccount;
use Modules\RequisitionSystem\Models\CostCenter;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Models\Status;
use Modules\RequisitionSystem\Support\BudgetWorkflow;

/**
 * Seeds chart of accounts + ICT 2026-2027 active budget from
 * FY26-27 Budget Cash Flow Projection ICT.xlsx (cols A/B/C).
 */
class IctBudgetProjectionSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['account_no' => '61100', 'description' => 'Tuition and Fees Current Year', 'parent_no' => null],
            ['account_no' => '61200', 'description' => 'Tuition and Fees Accrued', 'parent_no' => null],
            ['account_no' => '61300', 'description' => 'Government of Belize - Subvention', 'parent_no' => null],
            ['account_no' => '61600', 'description' => 'Agri-Business - Central Farm', 'parent_no' => null],
            ['account_no' => '61700', 'description' => 'Calabash Caye Field Station', 'parent_no' => null],
            ['account_no' => '61902', 'description' => 'Online Distance Learning (ODL)', 'parent_no' => null],
            ['account_no' => '62301', 'description' => 'UB FEA Academy', 'parent_no' => null],
            ['account_no' => '62600', 'description' => 'Other Income', 'parent_no' => null],
            ['account_no' => '62612', 'description' => 'Course Outlines', 'parent_no' => null],
            ['account_no' => '63501', 'description' => 'Capital Grant Revenue', 'parent_no' => null],
            ['account_no' => '62500', 'description' => 'Interest Income', 'parent_no' => null],
            ['account_no' => '62606', 'description' => 'Account 62606', 'parent_no' => null],
            ['account_no' => '70100', 'description' => 'Personal Emoluments', 'parent_no' => null],
            ['account_no' => '70101', 'description' => 'Salaries (Full Time Permanent)', 'parent_no' => '70100'],
            ['account_no' => '70102', 'description' => 'Salaries (Adjunct)', 'parent_no' => '70100'],
            ['account_no' => '70103', 'description' => 'Wages (Un-established Staff)', 'parent_no' => '70100'],
            ['account_no' => '70103.1', 'description' => 'Wages (Overtime)', 'parent_no' => '70103'],
            ['account_no' => '70104', 'description' => 'Allowances', 'parent_no' => '70100'],
            ['account_no' => '70105', 'description' => 'Social Security', 'parent_no' => '70100'],
            ['account_no' => '70106', 'description' => 'Honorarium', 'parent_no' => '70100'],
            ['account_no' => '70107', 'description' => 'Ex-Gratia Payment', 'parent_no' => '70100'],
            ['account_no' => '70108', 'description' => 'Pensions (UB Liabilities)', 'parent_no' => '70100'],
            ['account_no' => '70109', 'description' => 'Gratuities (Contractual)', 'parent_no' => '70100'],
            ['account_no' => '70110', 'description' => 'Gratuities (10 years + gratuities liability)', 'parent_no' => '70100'],
            ['account_no' => '70111', 'description' => 'Health Insurance (Life, Medical, Dental, Optical)', 'parent_no' => '70100'],
            ['account_no' => '70112', 'description' => 'Study Leave Replacement', 'parent_no' => '70100'],
            ['account_no' => '70113', 'description' => 'Promotion for Assoc. Prof.', 'parent_no' => '70100'],
            ['account_no' => '70114', 'description' => 'Promotion Senior Lecturer', 'parent_no' => '70100'],
            ['account_no' => '70115', 'description' => 'Severance Expense', 'parent_no' => '70100'],
            ['account_no' => '70116', 'description' => 'Vacation Expense', 'parent_no' => '70100'],
            ['account_no' => '71112', 'description' => 'Off Sequence', 'parent_no' => '70100'],
            ['account_no' => '71114', 'description' => 'Deferred Exam', 'parent_no' => '70100'],
            ['account_no' => '70200', 'description' => 'Travel & Subsistence', 'parent_no' => null],
            ['account_no' => '70201', 'description' => 'Mileage', 'parent_no' => '70200'],
            ['account_no' => '70202', 'description' => 'Subsistence', 'parent_no' => '70200'],
            ['account_no' => '70203', 'description' => 'Foreign Travel', 'parent_no' => '70200'],
            ['account_no' => '70204', 'description' => 'Other Travel Expenditures', 'parent_no' => '70200'],
            ['account_no' => '70300', 'description' => 'Materials & Supplies', 'parent_no' => null],
            ['account_no' => '70301', 'description' => 'Office Supplies', 'parent_no' => '70300'],
            ['account_no' => '70302', 'description' => 'Books & Periodicals', 'parent_no' => '70300'],
            ['account_no' => '70303', 'description' => 'Medical & Laboratory Supplies', 'parent_no' => '70300'],
            ['account_no' => '70304', 'description' => 'Uniforms', 'parent_no' => '70300'],
            ['account_no' => '70305', 'description' => 'Household Sundries', 'parent_no' => '70300'],
            ['account_no' => '70306', 'description' => 'Food', 'parent_no' => '70300'],
            ['account_no' => '70307', 'description' => 'Spraying Supplies', 'parent_no' => '70300'],
            ['account_no' => '70308', 'description' => 'Spares - Farm Machinery Equip.', 'parent_no' => '70300'],
            ['account_no' => '70309', 'description' => 'Animal Feed', 'parent_no' => '70300'],
            ['account_no' => '70310', 'description' => 'Animal Pasture', 'parent_no' => '70300'],
            ['account_no' => '70311', 'description' => 'Production Supplies', 'parent_no' => '70300'],
            ['account_no' => '70312', 'description' => 'School Supplies', 'parent_no' => '70300'],
            ['account_no' => '70313', 'description' => 'Building & Construction Supplies', 'parent_no' => '70300'],
            ['account_no' => '70314', 'description' => 'Computer Supplies', 'parent_no' => '70300'],
            ['account_no' => '70315', 'description' => 'Other Office Equipment', 'parent_no' => '70300'],
            ['account_no' => '70316', 'description' => 'Other Laboratory Supplies', 'parent_no' => '70300'],
            ['account_no' => '70317', 'description' => 'Test Equipment', 'parent_no' => '70300'],
            ['account_no' => '70318', 'description' => 'Other Equipment', 'parent_no' => '70300'],
            ['account_no' => '70319', 'description' => 'Teaching Supplies', 'parent_no' => '70300'],
            ['account_no' => '71318', 'description' => 'Meals Supplies', 'parent_no' => '70300'],
            ['account_no' => '70400', 'description' => 'Operating Costs', 'parent_no' => null],
            ['account_no' => '70401', 'description' => 'Fuel', 'parent_no' => '70400'],
            ['account_no' => '70402', 'description' => 'Advertising', 'parent_no' => '70400'],
            ['account_no' => '70403', 'description' => 'Miscellaneous', 'parent_no' => '70400'],
            ['account_no' => '70404', 'description' => 'Building Construction', 'parent_no' => '70400'],
            ['account_no' => '70405', 'description' => 'Mail Delivery', 'parent_no' => '70400'],
            ['account_no' => '70406', 'description' => 'Office Cleaning (House Keeping)', 'parent_no' => '70400'],
            ['account_no' => '70407', 'description' => 'Garbage Disposal', 'parent_no' => '70400'],
            ['account_no' => '70409', 'description' => 'Insurance - Computers', 'parent_no' => '70400'],
            ['account_no' => '70410', 'description' => 'Insurance - Buildings', 'parent_no' => '70400'],
            ['account_no' => '70411', 'description' => 'Insurance - Furniture, Equipment', 'parent_no' => '70400'],
            ['account_no' => '70412', 'description' => 'Insurance - Motor Vehicles', 'parent_no' => '70400'],
            ['account_no' => '70413', 'description' => 'Insurance - Liab & Other', 'parent_no' => '70400'],
            ['account_no' => '70414', 'description' => 'Bank Charges', 'parent_no' => '70400'],
            ['account_no' => '70415', 'description' => 'Bereavement Expeneses', 'parent_no' => '70400'],
            ['account_no' => '70416', 'description' => 'Employment Expense', 'parent_no' => '70400'],
            ['account_no' => '70417', 'description' => 'Entertainment', 'parent_no' => '70400'],
            ['account_no' => '70418', 'description' => 'Insurance - - Machinery & Equip', 'parent_no' => '70400'],
            ['account_no' => '70419', 'description' => 'Tax Assessmnt/Soc Sec Penalties', 'parent_no' => '70400'],
            ['account_no' => '70422', 'description' => 'Collection Expense', 'parent_no' => '70400'],
            ['account_no' => '70423', 'description' => 'Loss on Exchange Rate', 'parent_no' => '70400'],
            ['account_no' => '70424', 'description' => 'Capital Financing Costs', 'parent_no' => '70400'],
            ['account_no' => '71101', 'description' => 'Recruitment', 'parent_no' => '70400'],
            ['account_no' => '71102', 'description' => 'Orientation', 'parent_no' => '70400'],
            ['account_no' => '71103', 'description' => 'Registration', 'parent_no' => '70400'],
            ['account_no' => '71104', 'description' => 'Graduation', 'parent_no' => '70400'],
            ['account_no' => '71105', 'description' => 'Physical Education & Sports', 'parent_no' => '70400'],
            ['account_no' => '71106', 'description' => 'Field Trips', 'parent_no' => '70400'],
            ['account_no' => '71107', 'description' => 'Programming', 'parent_no' => '70400'],
            ['account_no' => '71107.1', 'description' => 'UB Anniversary', 'parent_no' => '71107'],
            ['account_no' => '71107.2', 'description' => 'Recognition & Awards', 'parent_no' => '71107'],
            ['account_no' => '71107.3', 'description' => 'Socializing', 'parent_no' => '71107'],
            ['account_no' => '71107.4', 'description' => 'Donation', 'parent_no' => '71107'],
            ['account_no' => '71108', 'description' => 'Student Services', 'parent_no' => '70400'],
            ['account_no' => '71109', 'description' => 'Publications / Reports', 'parent_no' => '70400'],
            ['account_no' => '71110', 'description' => 'RLC stipend', 'parent_no' => '70400'],
            ['account_no' => '71111', 'description' => 'UB Scholarships', 'parent_no' => '70400'],
            ['account_no' => '71116', 'description' => 'Sponsorship Expense', 'parent_no' => '70400'],
            ['account_no' => '71204', 'description' => 'Brokerage/ Custome Fees', 'parent_no' => '70400'],
            ['account_no' => '70500', 'description' => 'Maintenance Costs', 'parent_no' => null],
            ['account_no' => '70501', 'description' => 'Maintenance of Buildings', 'parent_no' => '70500'],
            ['account_no' => '70502', 'description' => 'Maintenance of Grounds', 'parent_no' => '70500'],
            ['account_no' => '70503', 'description' => 'Repairs & Maintenance of Furn. & Equip.', 'parent_no' => '70500'],
            ['account_no' => '70504', 'description' => 'Repairs & Maintenance of Vehicles', 'parent_no' => '70500'],
            ['account_no' => '70523', 'description' => 'Maintenance of Computer Hardware', 'parent_no' => '70500'],
            ['account_no' => '70524', 'description' => 'Maintenance of Computer Software', 'parent_no' => '70500'],
            ['account_no' => '70525', 'description' => 'Maintenance of Classroom & Lab. Equip.', 'parent_no' => '70500'],
            ['account_no' => '70526', 'description' => 'Maintenance of Other Equipment', 'parent_no' => '70500'],
            ['account_no' => '70537', 'description' => 'Depreciation Expense', 'parent_no' => null],
            ['account_no' => '70600', 'description' => 'Training', 'parent_no' => null],
            ['account_no' => '70408', 'description' => 'Conference', 'parent_no' => '70600'],
            ['account_no' => '70601', 'description' => 'Courses/Seminars', 'parent_no' => '70600'],
            ['account_no' => '70602', 'description' => 'Workshops', 'parent_no' => '70600'],
            ['account_no' => '70603', 'description' => 'Training Books and Materials', 'parent_no' => '70600'],
            ['account_no' => '70700', 'description' => 'Professional Services', 'parent_no' => null],
            ['account_no' => '70701', 'description' => 'Compensation - Auditing', 'parent_no' => '70700'],
            ['account_no' => '70702', 'description' => 'Compensation - Legal', 'parent_no' => '70700'],
            ['account_no' => '70703', 'description' => 'Consultancy', 'parent_no' => '70700'],
            ['account_no' => '70704', 'description' => 'Service Contract', 'parent_no' => '70700'],
            ['account_no' => '70704.1', 'description' => 'Service Agreement', 'parent_no' => '70704'],
            ['account_no' => '70705', 'description' => 'Translation', 'parent_no' => '70700'],
            ['account_no' => '71202', 'description' => 'Other Outside Services', 'parent_no' => '70700'],
            ['account_no' => '70800', 'description' => 'Public Utilities', 'parent_no' => null],
            ['account_no' => '70801', 'description' => 'Electricity', 'parent_no' => '70800'],
            ['account_no' => '70802', 'description' => 'Gas - Butane', 'parent_no' => '70800'],
            ['account_no' => '70803', 'description' => 'Water', 'parent_no' => '70800'],
            ['account_no' => '70804', 'description' => 'Telephones', 'parent_no' => '70800'],
            ['account_no' => '70805', 'description' => 'Internet', 'parent_no' => '70800'],
            ['account_no' => '70900', 'description' => 'Contributions, Licences & Subscriptions', 'parent_no' => null],
            ['account_no' => '70901', 'description' => 'Individual Research', 'parent_no' => '70900'],
            ['account_no' => '70902', 'description' => 'ATLIB', 'parent_no' => '70900'],
            ['account_no' => '70903', 'description' => 'ACTI', 'parent_no' => '70900'],
            ['account_no' => '70904', 'description' => 'COBEC', 'parent_no' => '70900'],
            ['account_no' => '70905', 'description' => 'CSUCA', 'parent_no' => '70900'],
            ['account_no' => '70906', 'description' => 'Others', 'parent_no' => '70900'],
            ['account_no' => '70920', 'description' => 'UB Scholarships & Grants', 'parent_no' => null],
            ['account_no' => '71000', 'description' => 'Rents', 'parent_no' => null],
            ['account_no' => '71001', 'description' => 'Rental of Office Space', 'parent_no' => '71000'],
            ['account_no' => '71002', 'description' => 'Housing for Visiting Faculty', 'parent_no' => '71000'],
            ['account_no' => '71003', 'description' => 'Rental of Other Buildings', 'parent_no' => '71000'],
            ['account_no' => '71004', 'description' => 'Rental of Office Equipment', 'parent_no' => '71000'],
            ['account_no' => '71005', 'description' => 'Rental of Other Equipment', 'parent_no' => '71000'],
            ['account_no' => '71006', 'description' => 'Rental of Vehicles', 'parent_no' => '71000'],
            ['account_no' => '71007', 'description' => 'Other Rentals', 'parent_no' => '71000'],
            ['account_no' => '71113', 'description' => 'Contingencies', 'parent_no' => null],
            ['account_no' => '71115', 'description' => 'Discount', 'parent_no' => '71113'],
            ['account_no' => '72001', 'description' => 'Loss on write-down inventory', 'parent_no' => '71113'],
            ['account_no' => '72002', 'description' => 'Bad Debt Expense', 'parent_no' => '71113'],
            ['account_no' => '72003', 'description' => 'Loss on Disposal of Assets', 'parent_no' => '71113'],
            ['account_no' => '72004', 'description' => 'Tuition Waiver Expense', 'parent_no' => '71113'],
            ['account_no' => '15100', 'description' => 'Capital Expenditures', 'parent_no' => null],
            ['account_no' => '15101', 'description' => 'Land & Land Improvements', 'parent_no' => '15100'],
            ['account_no' => '15102', 'description' => 'Buildings & Bldg Improvements', 'parent_no' => '15100'],
            ['account_no' => '15103', 'description' => 'Office Furniture & Fixtures', 'parent_no' => '15100'],
            ['account_no' => '15104', 'description' => 'Motor Vehilces', 'parent_no' => '15100'],
            ['account_no' => '15105', 'description' => 'Computer & Equipment', 'parent_no' => '15100'],
            ['account_no' => '15106', 'description' => 'Machinery & Equipment', 'parent_no' => '15100'],
            ['account_no' => '15107', 'description' => 'Lab Equipment', 'parent_no' => '15100'],
            ['account_no' => '15109', 'description' => 'Capital Work in Progresss', 'parent_no' => '15100'],
        ];

        $lineItems = [
            ['account_no' => '70101', 'amount' => 667371.61],
            ['account_no' => '70102', 'amount' => 0.00],
            ['account_no' => '70103.1', 'amount' => 0.00],
            ['account_no' => '70104', 'amount' => 20127.84],
            ['account_no' => '70105', 'amount' => 25884.04],
            ['account_no' => '70106', 'amount' => 0.00],
            ['account_no' => '70107', 'amount' => 0.00],
            ['account_no' => '70108', 'amount' => 0.00],
            ['account_no' => '70109', 'amount' => 11464.46],
            ['account_no' => '70110', 'amount' => 0.00],
            ['account_no' => '70111', 'amount' => 39981.60],
            ['account_no' => '70201', 'amount' => 4600.00],
            ['account_no' => '70202', 'amount' => 2000.00],
            ['account_no' => '70203', 'amount' => 6000.00],
            ['account_no' => '70204', 'amount' => 4000.00],
            ['account_no' => '70301', 'amount' => 1000.00],
            ['account_no' => '70302', 'amount' => 0.00],
            ['account_no' => '70303', 'amount' => 0.00],
            ['account_no' => '70304', 'amount' => 0.00],
            ['account_no' => '70305', 'amount' => 0.00],
            ['account_no' => '70306', 'amount' => 800.00],
            ['account_no' => '70307', 'amount' => 0.00],
            ['account_no' => '70308', 'amount' => 0.00],
            ['account_no' => '70309', 'amount' => 0.00],
            ['account_no' => '70310', 'amount' => 0.00],
            ['account_no' => '70311', 'amount' => 0.00],
            ['account_no' => '70312', 'amount' => 0.00],
            ['account_no' => '70313', 'amount' => 0.00],
            ['account_no' => '70314', 'amount' => 120000.00],
            ['account_no' => '70315', 'amount' => 4500.00],
            ['account_no' => '70410', 'amount' => 3049.77],
            ['account_no' => '70413', 'amount' => 166.66],
            ['account_no' => '70501', 'amount' => 30000.00],
            ['account_no' => '70502', 'amount' => 0.00],
            ['account_no' => '70503', 'amount' => 3000.00],
            ['account_no' => '70504', 'amount' => 0.00],
            ['account_no' => '70523', 'amount' => 55000.00],
            ['account_no' => '70537', 'amount' => 171408.00],
            ['account_no' => '70408', 'amount' => 10000.00],
            ['account_no' => '70601', 'amount' => 15000.00],
            ['account_no' => '70602', 'amount' => 8000.00],
            ['account_no' => '70701', 'amount' => 0.00],
            ['account_no' => '70702', 'amount' => 0.00],
            ['account_no' => '70703', 'amount' => 0.00],
            ['account_no' => '70704.1', 'amount' => 6000.00],
            ['account_no' => '70801', 'amount' => 20364.00],
            ['account_no' => '70803', 'amount' => 4125.00],
            ['account_no' => '70804', 'amount' => 2674.00],
            ['account_no' => '70805', 'amount' => 3841.00],
            ['account_no' => '70906', 'amount' => 307731.00],
            ['account_no' => '70920', 'amount' => 0.00],
            ['account_no' => '71000', 'amount' => 0.00],
            ['account_no' => '71113', 'amount' => 0.00],
            ['account_no' => '15100', 'amount' => 0.00],
        ];

        $this->seedAccounts($accounts);
        $this->seedIctBudget($lineItems);
    }

    /**
     * @param  list<array{account_no: string, description: string, parent_no: ?string}>  $accounts
     */
    private function seedAccounts(array $accounts): void
    {
        foreach ($accounts as $account) {
            ChartOfAccount::updateOrCreate(
                ['account_no' => $account['account_no']],
                ['description' => $account['description']]
            );
        }

        $byAccountNo = ChartOfAccount::query()
            ->whereIn('account_no', collect($accounts)->pluck('account_no'))
            ->get()
            ->keyBy('account_no');

        foreach ($accounts as $account) {
            $model = $byAccountNo->get($account['account_no']);
            if (!$model) {
                continue;
            }

            $parentId = null;
            if (!empty($account['parent_no'])) {
                $parentId = $byAccountNo->get($account['parent_no'])?->id;
            }

            if ((int) ($model->parent_id ?? 0) !== (int) ($parentId ?? 0)) {
                $model->update(['parent_id' => $parentId]);
            }
        }
    }

    /**
     * @param  list<array{account_no: string, amount: float}>  $lineItems
     */
    private function seedIctBudget(array $lineItems): void
    {
        $costCenter = CostCenter::firstOrCreate(['name' => 'ICT-001']);
        $year = BudgetYear::firstOrCreate(
            ['label' => '2026-2027'],
            ['submissions_open' => false]
        );

        $activeStatusId = Status::where('name', 'Active')->value('id');
        if (!$activeStatusId) {
            throw new \RuntimeException('Active status is missing. Run StatusSeeder first.');
        }

        $pipelineId = BudgetWorkflow::pipelineId();
        $finalSequence = BudgetWorkflow::maxPipelineSequence($pipelineId) ?? BudgetWorkflow::BUDGET_OFFICER_SEQUENCE;
        $finalStageId = BudgetWorkflow::stageIdForSequence($finalSequence, $pipelineId)
            ?? Stage::query()->value('id');

        $budget = Budget::query()
            ->where('cost_center_id', $costCenter->id)
            ->where('budget_year_id', $year->id)
            ->first();

        $attributes = [
            'pipeline_id' => $pipelineId ?: null,
            'status_id' => $activeStatusId,
            'stage_id' => $finalStageId,
            'current_stage_sequence' => $finalSequence,
            'notes' => 'Seeded from FY26-27 Budget Cash Flow Projection ICT.xlsx',
            'submitted_at' => now(),
        ];

        if ($budget) {
            $budget->update($attributes);
        } else {
            $budget = Budget::create(array_merge([
                'cost_center_id' => $costCenter->id,
                'budget_year_id' => $year->id,
            ], $attributes));
        }

        // Only one Active budget per cost center.
        Budget::query()
            ->where('cost_center_id', $costCenter->id)
            ->where('id', '!=', $budget->id)
            ->where('status_id', $activeStatusId)
            ->update([
                'status_id' => Status::where('name', 'Inactive')->value('id')
                    ?? $activeStatusId,
            ]);

        $accountIds = ChartOfAccount::query()
            ->whereIn('account_no', collect($lineItems)->pluck('account_no'))
            ->pluck('id', 'account_no');

        $budget->lineItems()->delete();

        foreach ($lineItems as $item) {
            $accountId = $accountIds->get($item['account_no']);
            if (!$accountId) {
                continue;
            }

            BudgetLineItem::create([
                'budget_id' => $budget->id,
                'chart_of_account_id' => $accountId,
                'amount' => $item['amount'],
                'notes' => null,
            ]);
        }
    }
}

