<?php

declare(strict_types=1);

namespace WendellAdriel\Idempotency\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \WendellAdriel\Idempotency\Providers\IdempotencyServiceProvider::class,
        ];
    }
}
