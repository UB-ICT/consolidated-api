<?php

namespace Modules\RequisitionSystem\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\RequisitionSystem\Models\Budget;
use Modules\RequisitionSystem\Models\BudgetLineItem;
use Modules\RequisitionSystem\Models\BudgetYear;
use Modules\RequisitionSystem\Models\ChartOfAccount;
use Modules\RequisitionSystem\Models\CostCenter;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Models\Status;
use Modules\RequisitionSystem\Support\BudgetWorkflow;
use Modules\RequisitionSystem\Support\SimpleXlsxReader;
use RuntimeException;

class BudgetCashFlowImportService
{
    public function parse(UploadedFile $file): array
    {
        return $this->parseRows(SimpleXlsxReader::rows($file));
    }

    /**
     * @return array{
     *   accounts: list<array{account_no: string, description: string, parent_no: ?string}>,
     *   line_items: list<array{account_no: string, amount: float}>
     * }
     */
    public function parsePath(string $path, bool $fillMissingAmounts = false): array
    {
        return $this->parseRows(SimpleXlsxReader::rowsFromPath($path), $fillMissingAmounts);
    }

    /**
     * @param  list<array{0: mixed, 1: mixed, 2: mixed}>  $rows
     * @return array{
     *   accounts: list<array{account_no: string, description: string, parent_no: ?string}>,
     *   line_items: list<array{account_no: string, amount: float}>
     * }
     */
    private function parseRows(array $rows, bool $fillMissingAmounts = false): array
    {
        if ($rows === []) {
            throw new RuntimeException('The uploaded spreadsheet is empty.');
        }

        $raw = [];

        foreach ($rows as $index => $row) {
            $accountRaw = $row[0] ?? null;
            $descriptionRaw = $row[1] ?? null;
            $amountRaw = $row[2] ?? null;

            if ($accountRaw === null || $accountRaw === '') {
                continue;
            }

            $accountNo = $this->normalizeAccountNo($accountRaw);

            if ($accountNo === null) {
                continue;
            }

            $descriptionString = $descriptionRaw === null ? '' : (string) $descriptionRaw;
            $indent = strlen($descriptionString) - strlen(ltrim($descriptionString, ' '));
            $description = trim($descriptionString);

            if ($description === '') {
                $description = 'Account '.$accountNo;
            }

            $amount = $this->normalizeAmount($amountRaw);

            $raw[] = [
                'account_no' => $accountNo,
                'description' => $description,
                'indent' => $indent,
                'amount' => $amount,
            ];
        }

        if ($raw === []) {
            throw new RuntimeException(
                'No account rows found. Expected column A = account number, B = description, C = amount.'
            );
        }

        return $this->buildHierarchyAndLineItems($raw, $fillMissingAmounts);
    }

    /**
     * @param  list<array{account_no: string, description: string, parent_no: ?string}>  $accounts
     * @param  list<array{account_no: string, amount: float}>  $lineItems
     */
    public function import(
        CostCenter $costCenter,
        BudgetYear $year,
        array $accounts,
        array $lineItems,
        string $statusName,
        ?string $notes = null,
        bool $syncAccounts = true
    ): Budget {
        return DB::connection('porsql')->transaction(function () use (
            $costCenter,
            $year,
            $accounts,
            $lineItems,
            $statusName,
            $notes,
            $syncAccounts
        ) {
            if ($syncAccounts) {
                $this->syncAccounts($accounts);
            }

            $statusId = Status::where('name', $statusName)->value('id');

            if (!$statusId) {
                throw new RuntimeException(sprintf('Status "%s" was not found.', $statusName));
            }

            $pipelineId = BudgetWorkflow::pipelineId();
            $sequence = match ($statusName) {
                'Draft' => BudgetWorkflow::DRAFT_STAGE_SEQUENCE,
                default => BudgetWorkflow::maxPipelineSequence($pipelineId)
                    ?? BudgetWorkflow::BUDGET_OFFICER_SEQUENCE,
            };
            $stageId = BudgetWorkflow::stageIdForSequence($sequence, $pipelineId)
                ?? Stage::query()->value('id');

            $budget = Budget::query()
                ->where('cost_center_id', $costCenter->id)
                ->where('budget_year_id', $year->id)
                ->first();

            $attributes = [
                'pipeline_id' => $pipelineId ?: null,
                'status_id' => $statusId,
                'stage_id' => $stageId,
                'current_stage_sequence' => $sequence,
                'notes' => $notes,
                'submitted_at' => $statusName === 'Draft' ? null : now(),
            ];

            if ($budget) {
                $inFlightConflict = Budget::query()
                    ->active()
                    ->where('cost_center_id', $costCenter->id)
                    ->where('id', '!=', $budget->id)
                    ->exists();

                if (in_array($statusName, BudgetWorkflow::inFlightStatuses(), true) && $inFlightConflict) {
                    throw new RuntimeException(
                        'This cost center already has another in-progress budget. Finish or close it before uploading as Draft/Pending.'
                    );
                }

                $budget->update($attributes);
            } else {
                if (
                    in_array($statusName, BudgetWorkflow::inFlightStatuses(), true)
                    && Budget::query()->active()->where('cost_center_id', $costCenter->id)->exists()
                ) {
                    throw new RuntimeException(
                        'This cost center already has an in-progress budget. Finish or close it before uploading a new Draft/Pending budget.'
                    );
                }

                $budget = Budget::create(array_merge([
                    'cost_center_id' => $costCenter->id,
                    'budget_year_id' => $year->id,
                ], $attributes));
            }

            if ($statusName === 'Active') {
                BudgetWorkflow::applyActivation($budget->fresh());
                $budget->refresh();
            }

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

            return $budget->fresh([
                'costCenter',
                'budgetYear',
                'status',
                'stage',
                'lineItems.chartOfAccount',
            ]);
        });
    }

    /**
     * @param  list<array{account_no: string, description: string, indent: int, amount: ?float}>  $raw
     * @return array{
     *   accounts: list<array{account_no: string, description: string, parent_no: ?string}>,
     *   line_items: list<array{account_no: string, amount: float}>
     * }
     */
    private function buildHierarchyAndLineItems(array $raw, bool $fillMissingAmounts = false): array
    {
        $byNo = [];
        $order = [];

        foreach ($raw as $row) {
            $accountNo = $row['account_no'];

            if (!isset($byNo[$accountNo])) {
                $byNo[$accountNo] = $row;
                $order[] = $accountNo;
                continue;
            }

            $existing = &$byNo[$accountNo];

            if (strlen($row['description']) > strlen($existing['description'])) {
                $existing['description'] = $row['description'];
            }

            if ($existing['amount'] === null && $row['amount'] !== null) {
                $existing['amount'] = $row['amount'];
            }

            $existing['indent'] = min($existing['indent'], $row['indent']);
            unset($existing);
        }

        $accounts = [];
        $stack = [];

        foreach ($order as $accountNo) {
            $row = $byNo[$accountNo];

            while ($stack !== [] && $stack[array_key_last($stack)]['indent'] >= $row['indent']) {
                array_pop($stack);
            }

            $parentNo = null;

            if ($row['indent'] > 0 && $stack !== []) {
                $parentNo = $stack[array_key_last($stack)]['account_no'];
            }

            if (str_contains($accountNo, '.')) {
                $dottedParent = substr($accountNo, 0, (int) strrpos($accountNo, '.'));

                if (isset($byNo[$dottedParent])) {
                    $parentNo = $dottedParent;
                }
            }

            if ($parentNo === $accountNo) {
                $parentNo = null;
            }

            $accounts[] = [
                'account_no' => $accountNo,
                'description' => $row['description'],
                'parent_no' => $parentNo,
                'amount' => $row['amount'],
            ];

            $stack[] = [
                'indent' => $row['indent'],
                'account_no' => $accountNo,
            ];
        }

        $childrenWithAmounts = [];
        $childrenByParent = [];

        foreach ($accounts as $account) {
            if ($account['parent_no'] === null) {
                continue;
            }

            $childrenByParent[$account['parent_no']][] = $account['account_no'];

            if ($account['amount'] === null) {
                continue;
            }

            $childrenWithAmounts[$account['parent_no']][] = $account['account_no'];
        }

        $lineItems = [];

        foreach ($accounts as $account) {
            $hasChildren = $fillMissingAmounts
                ? !empty($childrenByParent[$account['account_no']])
                : !empty($childrenWithAmounts[$account['account_no']]);

            if ($hasChildren) {
                continue;
            }

            if ($account['amount'] === null && !$fillMissingAmounts) {
                continue;
            }

            $lineItems[] = [
                'account_no' => $account['account_no'],
                'amount' => round((float) ($account['amount'] ?? 0), 2),
            ];
        }

        if ($lineItems === []) {
            throw new RuntimeException(
                'No budget amounts found in column C. Add numeric values for the accounts to import.'
            );
        }

        return [
            'accounts' => array_map(
                static fn (array $account) => [
                    'account_no' => $account['account_no'],
                    'description' => $account['description'],
                    'parent_no' => $account['parent_no'],
                ],
                $accounts
            ),
            'line_items' => $lineItems,
        ];
    }

    /**
     * @param  list<array{account_no: string, description: string, parent_no: ?string}>  $accounts
     */
    private function syncAccounts(array $accounts): void
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

    private function normalizeAccountNo(mixed $value): ?string
    {
        if (is_int($value) || is_float($value)) {
            if ((float) $value == (int) $value) {
                return (string) (int) $value;
            }

            $asString = rtrim(rtrim(sprintf('%.8F', $value), '0'), '.');

            return preg_match('/^\d+(\.\d+)?$/', $asString) ? $asString : null;
        }

        $accountNo = trim((string) $value);

        if (in_array(strtolower($accountNo), ['account', 'no.', 'no'], true)) {
            return null;
        }

        return preg_match('/^\d+(\.\d+)?$/', $accountNo) ? $accountNo : null;
    }

    private function normalizeAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $normalized = str_replace([',', ' '], '', trim((string) $value));

        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }
}
