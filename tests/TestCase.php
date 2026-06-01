<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\InteractsWithApi;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithApi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureTestingDatabaseConnections();
    }
}
