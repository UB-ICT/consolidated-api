<?php

namespace Modules\RequisitionSystem\Tests\Unit\Support;

use Modules\RequisitionSystem\Support\RequisitionLogAction;
use PHPUnit\Framework\TestCase;

class RequisitionLogActionTest extends TestCase
{
    public function test_all_includes_cost_center_review(): void
    {
        $this->assertContains(
            RequisitionLogAction::COST_CENTER_REVIEW,
            RequisitionLogAction::all()
        );
    }

    public function test_cost_center_review_constant_value(): void
    {
        $this->assertSame('cost_center_review', RequisitionLogAction::COST_CENTER_REVIEW);
    }
}
