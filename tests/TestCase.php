<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        // Existing feature suites test the underlying modules independently.
        // SaaS gate behavior has its own focused suite which enables this flag.
        config(['saas.saas_voucher_enabled' => false]);
    }
}
