<?php

namespace Modules\RequisitionSystem\Tests\Unit\Support;

use Modules\RequisitionSystem\Support\RequisitionLinePricing;
use Tests\TestCase;

class RequisitionLinePricingTest extends TestCase
{
    public function test_distributes_percent_discount_then_applies_gst(): void
    {
        $result = RequisitionLinePricing::calculate(
            [
                [
                    'quantity' => 2,
                    'unit_cost' => 100,
                    'gst_applicable' => true,
                    'chart_of_account_id' => 1,
                ],
                [
                    'quantity' => 1,
                    'unit_cost' => 50,
                    'gst_applicable' => false,
                    'chart_of_account_id' => 2,
                ],
            ],
            RequisitionLinePricing::DISCOUNT_PERCENT,
            10,
            12.5
        );

        $this->assertSame(250.0, $result['lines_subtotal']);
        $this->assertSame(25.0, $result['discount_amount']);
        $this->assertSame(22.5, $result['gst_total']);
        $this->assertSame(247.5, $result['total']);
        $this->assertSame(20.0, $result['items'][0]['discount_amount']);
        $this->assertSame(22.5, $result['items'][0]['gst_amount']);
        $this->assertSame(202.5, $result['items'][0]['total']);
        $this->assertSame(5.0, $result['items'][1]['discount_amount']);
        $this->assertSame(0.0, $result['items'][1]['gst_amount']);
        $this->assertSame(45.0, $result['items'][1]['total']);
    }

    public function test_no_gst_when_not_applicable(): void
    {
        $result = RequisitionLinePricing::calculate(
            [
                [
                    'quantity' => 1,
                    'unit_cost' => 100,
                    'gst_applicable' => false,
                    'chart_of_account_id' => 1,
                ],
            ],
            RequisitionLinePricing::DISCOUNT_NONE,
            0,
            12.5
        );

        $this->assertSame(0.0, $result['gst_total']);
        $this->assertSame(100.0, $result['total']);
    }

    public function test_allows_negative_quantity_credit_lines(): void
    {
        $result = RequisitionLinePricing::calculate(
            [
                [
                    'quantity' => 2,
                    'unit_cost' => 100,
                    'gst_applicable' => true,
                    'chart_of_account_id' => 1,
                ],
                [
                    'quantity' => -1,
                    'unit_cost' => 50,
                    'gst_applicable' => false,
                    'chart_of_account_id' => 2,
                ],
            ],
            RequisitionLinePricing::DISCOUNT_NONE,
            0,
            12.5
        );

        $this->assertSame(150.0, $result['lines_subtotal']);
        $this->assertSame(-50.0, $result['items'][1]['subtotal']);
        $this->assertSame(-50.0, $result['items'][1]['total']);
        $this->assertSame(25.0, $result['items'][0]['gst_amount']);
        $this->assertSame(175.0, $result['total']);
    }

    public function test_preserves_fractional_quantities(): void
    {
        $result = RequisitionLinePricing::calculate(
            [
                [
                    'quantity' => 1.5,
                    'unit_cost' => 100,
                    'gst_applicable' => false,
                    'chart_of_account_id' => 1,
                ],
                [
                    'quantity' => 0.25,
                    'unit_cost' => 40,
                    'gst_applicable' => false,
                    'chart_of_account_id' => 2,
                ],
            ],
            RequisitionLinePricing::DISCOUNT_NONE,
            0,
            12.5
        );

        $this->assertSame(1.5, $result['items'][0]['quantity']);
        $this->assertSame(0.25, $result['items'][1]['quantity']);
        $this->assertSame(160.0, $result['lines_subtotal']);
        $this->assertSame(160.0, $result['total']);
    }
}
