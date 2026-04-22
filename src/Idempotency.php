<?php

declare(strict_types=1);

namespace WendellAdriel\Idempotency;

use Illuminate\Support\Str;

final class Idempotency
{
    public static function key(): string
    {
        return Str::random(64);
    }
}
