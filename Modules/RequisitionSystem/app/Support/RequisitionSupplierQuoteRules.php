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

    /**
     * @param  array<int, array<string, mixed>>  $suppliers
     * @return array<string, string>
     */
    public static function validateSuppliers(array $suppliers, float $requisitionTotal): array
    {
        $errors = [];
        $suppliers = collect($suppliers)
            ->filter(fn (array $supplier) => !empty($supplier['supplier_id']))
            ->values();

        $requiredCount = self::requiredQuoteCount($requisitionTotal);
        $quoteCount = $suppliers->count();

        if ($quoteCount < $requiredCount) {
            if ($requiredCount === 1) {
                $errors['suppliers'] = 'Add at least one supplier quote.';
            } else {
                $errors['suppliers'] = sprintf(
                    'Requisitions of %s or more require at least %d supplier quotes.',
                    number_format(self::HIGH_VALUE_THRESHOLD, 0),
                    $requiredCount
                );
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
