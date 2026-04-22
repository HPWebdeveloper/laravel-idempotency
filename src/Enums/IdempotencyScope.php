<?php

declare(strict_types=1);

namespace WendellAdriel\Idempotency\Enums;

use InvalidArgumentException;

enum IdempotencyScope: string
{
    case User = 'user';
    case Ip = 'ip';
    case Global = 'global';

    public static function fromConfig(null|string|self $value = null): self
    {
        if ($value instanceof self) {
            return $value;
        }

        $value ??= config()->string('idempotency.scope');

        return self::tryFrom($value)
            ?? throw new InvalidArgumentException(sprintf('Unsupported idempotency scope [%s].', $value));
    }
}
