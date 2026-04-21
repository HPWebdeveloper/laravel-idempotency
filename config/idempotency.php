<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Idempotency Cache Store
    |--------------------------------------------------------------------------
    |
    | Define the cache store used to persist idempotency keys and responses.
    | Use null to fallback to Laravel's default cache store.
    |
    */
    'store' => null,

    /*
    |--------------------------------------------------------------------------
    | Idempotency TTL
    |--------------------------------------------------------------------------
    |
    | Number of seconds an idempotency key should remain valid.
    |
    */
    'ttl' => 3600,
];
