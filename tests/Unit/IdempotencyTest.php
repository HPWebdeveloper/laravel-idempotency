<?php

declare(strict_types=1);

use WendellAdriel\Idempotency\Idempotency;

test('it exposes a package version', function () {
    expect(Idempotency::version())->toBe('0.1.0');
});

test('it registers the package config', function () {
    expect(config('idempotency.ttl'))->toBe(3600)
        ->and(config('idempotency.store'))->toBeNull();
});
