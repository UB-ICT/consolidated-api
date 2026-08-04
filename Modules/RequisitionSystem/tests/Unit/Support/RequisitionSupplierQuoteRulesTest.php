<?php

namespace Modules\RequisitionSystem\Tests\Unit\Support;

use Modules\RequisitionSystem\Support\RequisitionLinePricing;
use Modules\RequisitionSystem\Support\RequisitionSupplierQuoteRules;
use Tests\TestCase;

class RequisitionSupplierQuoteRulesTest extends TestCase
{
    public function test_required_quote_count_by_total(): void
    {
        $this->assertSame(1, RequisitionSupplierQuoteRules::requiredQuoteCount(999.99));
        $this->assertSame(1, RequisitionSupplierQuoteRules::requiredQuoteCount(0));
        $this->assertSame(3, RequisitionSupplierQuoteRules::requiredQuoteCount(1000));
        $this->assertSame(3, RequisitionSupplierQuoteRules::requiredQuoteCount(2500));
    }

    public function test_calculate_items_total(): void
    {
        $total = RequisitionLinePricing::calculate(
            [
                ['quantity' => 2, 'unit_cost' => 50, 'gst_applicable' => false],
                ['quantity' => 1, 'unit_cost' => 25.5, 'gst_applicable' => false],
            ],
            RequisitionLinePricing::DISCOUNT_NONE,
            0,
            0
        )['total'];

        $this->assertSame(125.5, $total);
    }

    public function test_validate_suppliers_requires_one_quote_below_threshold(): void
    {
        $errors = RequisitionSupplierQuoteRules::validateSuppliers([], 500);

        $this->assertArrayHasKey('suppliers', $errors);
    }

    public function test_validate_suppliers_requires_three_quotes_at_threshold(): void
    {
        $suppliers = [
            ['supplier_id' => 1, 'is_recommended' => true],
            ['supplier_id' => 2, 'is_recommended' => false],
        ];

        $errors = RequisitionSupplierQuoteRules::validateSuppliers($suppliers, 1000);

        $this->assertArrayHasKey('suppliers', $errors);
    }

    public function test_validate_suppliers_requires_preferred_supplier_when_multiple_quotes(): void
    {
        $suppliers = [
            ['supplier_id' => 1, 'is_recommended' => false],
            ['supplier_id' => 2, 'is_recommended' => false],
        ];

        $errors = RequisitionSupplierQuoteRules::validateSuppliers($suppliers, 500);

        $this->assertSame(
            'Select exactly one preferred supplier when multiple supplier quotes are submitted.',
            $errors['suppliers']
        );
    }

    public function test_validate_suppliers_requires_preferred_supplier_for_high_value(): void
    {
        $suppliers = [
            ['supplier_id' => 1, 'is_recommended' => false],
            ['supplier_id' => 2, 'is_recommended' => false],
            ['supplier_id' => 3, 'is_recommended' => false],
        ];

        $errors = RequisitionSupplierQuoteRules::validateSuppliers($suppliers, 1500);

        $this->assertSame(
            'Select exactly one preferred supplier when multiple supplier quotes are submitted.',
            $errors['suppliers']
        );
    }

    public function test_validate_suppliers_allows_multiple_quotes_with_one_preferred(): void
    {
        $suppliers = [
            ['supplier_id' => 1, 'is_recommended' => true],
            ['supplier_id' => 2, 'is_recommended' => false],
        ];

        $errors = RequisitionSupplierQuoteRules::validateSuppliers($suppliers, 500);

        $this->assertSame([], $errors);
    }

    public function test_validate_suppliers_rejects_multiple_preferred_suppliers(): void
    {
        $suppliers = [
            ['supplier_id' => 1, 'is_recommended' => true],
            ['supplier_id' => 2, 'is_recommended' => true],
        ];

        $errors = RequisitionSupplierQuoteRules::validateSuppliers($suppliers, 500);

        $this->assertSame(
            'Select exactly one preferred supplier when multiple supplier quotes are submitted.',
            $errors['suppliers']
        );
    }

    public function test_validate_suppliers_allows_single_quote_below_threshold_without_recommended_flag(): void
    {
        $suppliers = [
            ['supplier_id' => 1, 'is_recommended' => false],
        ];

        $errors = RequisitionSupplierQuoteRules::validateSuppliers($suppliers, 500);

        $this->assertSame([], $errors);
    }

    public function test_normalize_recommended_supplier_for_single_quote(): void
    {
        $suppliers = RequisitionSupplierQuoteRules::normalizeRecommendedSupplier([
            ['supplier_id' => 7, 'is_recommended' => false],
        ]);

        $this->assertTrue($suppliers[0]['is_recommended']);
    }
}
