<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Aset Vite tidak perlu dibangun (npm run build) untuk menjalankan test.
        $this->withoutVite();
    }
}
