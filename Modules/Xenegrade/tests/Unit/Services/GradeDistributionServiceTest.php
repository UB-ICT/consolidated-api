<?php

namespace Modules\Xenegrade\Tests\Unit\Services;

use Modules\Xenegrade\Services\GradeDistributionService;
use PHPUnit\Framework\TestCase;

class GradeDistributionServiceTest extends TestCase
{
    public function test_query_rows_method_exists(): void
    {
        $this->assertTrue(method_exists(GradeDistributionService::class, 'queryRows'));
    }
}
