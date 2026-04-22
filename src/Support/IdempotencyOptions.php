<?php

declare(strict_types=1);

namespace WendellAdriel\Idempotency\Support;

use WendellAdriel\Idempotency\Enums\IdempotencyScope;

final readonly class IdempotencyOptions
{
    public function __construct(
        public int $ttl,
        public int $lockTimeout,
        public bool $required,
        public IdempotencyScope $scope,
        public string $header,
    ) {}

    public static function resolve(
        null|int|string $ttl = null,
        null|int|string $lockTimeout = null,
        null|bool|string $required = null,
        null|string|IdempotencyScope $scope = null,
        ?string $header = null,
    ): self {
        return new self(
            ttl: self::resolveTtl($ttl),
            lockTimeout: self::resolveLockTimeout($lockTimeout),
            required: self::resolveRequired($required),
            scope: IdempotencyScope::fromConfig($scope),
            header: $header ?? config()->string('idempotency.header'),
        );
    }

    public function serialize(): string
    {
        return implode(',', [
            $this->ttl,
            $this->lockTimeout,
            $this->required ? '1' : '0',
            $this->scope->value,
            $this->header,
        ]);
    }

    private static function resolveTtl(null|int|string $ttl): int
    {
        if (is_int($ttl)) {
            return $ttl;
        }

        return $ttl !== null
            ? (int) $ttl
            : config()->integer('idempotency.ttl');
    }

    private static function resolveLockTimeout(null|int|string $lockTimeout): int
    {
        if (is_int($lockTimeout)) {
            return $lockTimeout;
        }

        return $lockTimeout !== null
            ? (int) $lockTimeout
            : config()->integer('idempotency.lock_timeout');
    }

    private static function resolveRequired(null|bool|string $required): bool
    {
        if (is_bool($required)) {
            return $required;
        }

        return $required !== null
            ? filter_var($required, FILTER_VALIDATE_BOOLEAN)
            : config()->boolean('idempotency.required');
    }
}
