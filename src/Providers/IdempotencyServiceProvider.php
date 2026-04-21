<?php

declare(strict_types=1);

namespace WendellAdriel\Idempotency\Providers;

use Illuminate\Support\ServiceProvider;
use Override;

final class IdempotencyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../../config/idempotency.php' => base_path('config/idempotency.php'),
            ],
            'config'
        );
    }

    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/idempotency.php', 'idempotency');
    }
}
