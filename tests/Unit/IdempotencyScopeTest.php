<?php

declare(strict_types=1);

use WendellAdriel\Idempotency\Enums\IdempotencyScope;

test('it resolves enum backed scope values', function (): void {
    expect(IdempotencyScope::fromConfig('user'))->toBe(IdempotencyScope::User)
        ->and(IdempotencyScope::fromConfig('ip'))->toBe(IdempotencyScope::Ip)
        ->and(IdempotencyScope::fromConfig('global'))->toBe(IdempotencyScope::Global);
});

test('it resolves the default scope from config', function (): void {
    config()->set('idempotency.scope', 'global');

    expect(IdempotencyScope::fromConfig())->toBe(IdempotencyScope::Global);
});

test('it rejects invalid scope values', function (): void {
    expect(static fn (): IdempotencyScope => IdempotencyScope::fromConfig('tenant'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported idempotency scope [tenant].');
});
