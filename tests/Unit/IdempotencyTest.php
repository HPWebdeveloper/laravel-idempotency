<?php

declare(strict_types=1);

use WendellAdriel\Idempotency\Idempotency;

test('it generates a 64 character idempotency key', function (): void {
    expect(Idempotency::key())
        ->toBeString()
        ->toHaveLength(64);
});

test('it generates a unique idempotency key for each call', function (): void {
    expect(Idempotency::key())->not->toBe(Idempotency::key());
});

test('it registers the expanded package config defaults', function (): void {
    expect(config()->integer('idempotency.ttl'))->toBe(3600)
        ->and(config()->integer('idempotency.lock_timeout'))->toBe(10)
        ->and(config()->boolean('idempotency.required'))->toBeTrue()
        ->and(config('idempotency.scope'))->toBe('user')
        ->and(config('idempotency.header'))->toBe('Idempotency-Key');
});

test('it supports env-backed config overrides', function (): void {
    putenv('IDEMPOTENCY_TTL=120');
    putenv('IDEMPOTENCY_LOCK_TIMEOUT=45');
    putenv('IDEMPOTENCY_REQUIRED=false');
    putenv('IDEMPOTENCY_SCOPE=global');
    putenv('IDEMPOTENCY_HEADER=X-Idempotency-Key');

    $config = require __DIR__ . '/../../config/idempotency.php';

    expect((int) $config['ttl'])->toBe(120)
        ->and((int) $config['lock_timeout'])->toBe(45)
        ->and((bool) $config['required'])->toBeFalse()
        ->and($config['scope'])->toBe('global')
        ->and($config['header'])->toBe('X-Idempotency-Key');

    putenv('IDEMPOTENCY_TTL');
    putenv('IDEMPOTENCY_LOCK_TIMEOUT');
    putenv('IDEMPOTENCY_REQUIRED');
    putenv('IDEMPOTENCY_SCOPE');
    putenv('IDEMPOTENCY_HEADER');
});
