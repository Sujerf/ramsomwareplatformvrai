<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Disable CSRF verification for all feature tests.
     *
     * CSRF token validation tests authorization logic, not role/access logic.
     * Tests that specifically need CSRF validation can re-enable it with:
     *   $this->withMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
     */
    protected function setUp(): void
    {
        parent::setUp();

        // In Laravel 11, CSRF is handled by PreventRequestForgery (not ValidateCsrfToken)
        $this->withoutMiddleware(
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class
        );
    }
}
