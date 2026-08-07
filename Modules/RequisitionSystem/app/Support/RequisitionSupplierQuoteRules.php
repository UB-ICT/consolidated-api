<?php

namespace Modules\RequisitionSystem\Support;

final class RequisitionSupplierQuoteRules
{
    public const HIGH_VALUE_THRESHOLD = 1000;

    public static function requiredQuoteCount(float $requisitionTotal): int
    {
        return $requisitionTotal >= self::HIGH_VALUE_THRESHOLD ? 3 : 1;
    }

    public static function calculateItemsTotal(array $items): float
    {
        return RequisitionLinePricing::calculate($items)['total'];
    }

    public static function normalizeWaiverReason(?string $reason): ?string
    {
        if ($reason === null) {
            return null;
        }

        $trimmed = trim($reason);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param  array<int, array<string, mixed>>  $suppliers
     * @return array<string, string>
     */
    public static function validateSuppliers(
        array $suppliers,
        float $requisitionTotal,
        ?string $quoteWaiverReason = null
    ): array {
        $errors = [];
        $suppliers = collect($suppliers)
            ->filter(fn (array $supplier) => !empty($supplier['supplier_id']))
            ->values();

        $requiredCount = self::requiredQuoteCount($requisitionTotal);
        $quoteCount = $suppliers->count();
        $waiverReason = self::normalizeWaiverReason($quoteWaiverReason);
        $hasWaiver = $waiverReason !== null;

        if ($quoteCount < 1) {
            $errors['suppliers'] = 'Add at least one supplier quote.';

            return $errors;
        }

        if ($quoteCount < $requiredCount && !$hasWaiver) {
            if ($requiredCount === 1) {
                $errors['suppliers'] = 'Add at least one supplier quote.';
            } else {
                $errors['suppliers'] = sprintf(
                    'Requisitions of %s or more require at least %d supplier quotes, or provide a reason for fewer quotes.',
                    number_format(self::HIGH_VALUE_THRESHOLD, 0),
                    $requiredCount
                );
                $errors['quote_waiver_reason'] = 'Provide a reason when submitting fewer than three supplier quotes.';
            }

            return $errors;
        }

        $recommendedCount = $suppliers
            ->filter(fn (array $supplier) => (bool) ($supplier['is_recommended'] ?? false))
            ->count();

        if ($quoteCount === 1) {
            return $errors;
        }

        if ($recommendedCount !== 1) {
            $errors['suppliers'] = 'Select exactly one preferred supplier when multiple supplier quotes are submitted.';
        }

        return $errors;
    }

    /**
     * Keep a waiver reason only when high-value requisitions have fewer than three quotes.
     *
     * @param  array<int, array<string, mixed>>  $suppliers
     */
    public static function resolveStoredWaiverReason(
        array $suppliers,
        float $requisitionTotal,
        ?string $quoteWaiverReason
    ): ?string {
        $quoteCount = collect($suppliers)
            ->filter(fn (array $supplier) => !empty($supplier['supplier_id']))
            ->count();

        $requiredCount = self::requiredQuoteCount($requisitionTotal);

        if ($quoteCount >= $requiredCount) {
            return null;
        }

        return self::normalizeWaiverReason($quoteWaiverReason);
    }

    /**
     * @param  array<int, array<string, mixed>>  $suppliers
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeRecommendedSupplier(array $suppliers): array
    {
        $suppliers = collect($suppliers)
            ->filter(fn (array $supplier) => !empty($supplier['supplier_id']))
            ->values()
            ->all();

        if (count($suppliers) !== 1) {
            return $suppliers;
        }

        $suppliers[0]['is_recommended'] = true;

        return $suppliers;
    }
}
