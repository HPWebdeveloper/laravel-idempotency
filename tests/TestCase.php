<?php

declare(strict_types=1);

namespace WendellAdriel\Idempotency\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use WendellAdriel\Idempotency\Providers\IdempotencyServiceProvider;

class TestCase extends BaseTestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
    }

    protected function getPackageProviders($app): array
    {
        return [
            IdempotencyServiceProvider::class,
        ];
    }
}
