<?php

declare(strict_types=1);

use WendellAdriel\Idempotency\Idempotency;
use WendellAdriel\Idempotency\Support\IdempotencyOptions;

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

test('lock_timeout of zero is rejected', function (): void {
    expect(fn (): IdempotencyOptions => IdempotencyOptions::resolve(lockTimeout: 0))
        ->toThrow(InvalidArgumentException::class, 'The lock_timeout must be a positive integer (>= 1).');
});

test('negative lock_timeout is rejected', function (): void {
    expect(fn (): IdempotencyOptions => IdempotencyOptions::resolve(lockTimeout: -5))
        ->toThrow(InvalidArgumentException::class, 'The lock_timeout must be a positive integer (>= 1).');
});

test('lock_timeout of zero from config is rejected', function (): void {
    config()->set('idempotency.lock_timeout', 0);

    expect(fn (): IdempotencyOptions => IdempotencyOptions::resolve())
        ->toThrow(InvalidArgumentException::class, 'The lock_timeout must be a positive integer (>= 1).');
});

test('negative lock_timeout from config is rejected', function (): void {
    config()->set('idempotency.lock_timeout', -3);

    expect(fn (): IdempotencyOptions => IdempotencyOptions::resolve())
        ->toThrow(InvalidArgumentException::class, 'The lock_timeout must be a positive integer (>= 1).');
});

test('lock_timeout of one is the minimum accepted value', function (): void {
    $options = IdempotencyOptions::resolve(lockTimeout: 1);

    expect($options->lockTimeout)->toBe(1);
});

test('string-form negative lock_timeout is rejected', function (): void {
    // Route middleware parameters arrive as strings (e.g. "idempotent:...,-1").
    expect(fn (): IdempotencyOptions => IdempotencyOptions::resolve(lockTimeout: '-1'))
        ->toThrow(InvalidArgumentException::class, 'The lock_timeout must be a positive integer (>= 1).');
});
