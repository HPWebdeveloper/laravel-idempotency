<?php

declare(strict_types=1);

namespace WendellAdriel\Idempotency\Providers;

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Override;
use WendellAdriel\Idempotency\Http\Middleware\Idempotent;
use WendellAdriel\Idempotency\Support\IdempotencyCache;

final class IdempotencyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../../config/idempotency.php' => base_path('config/idempotency.php'),
            ],
            ['idempotency', 'idempotency-config']
        );
    }

    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/idempotency.php', 'idempotency');

        $this->app->singleton(IdempotencyCache::class, function (Application $app): IdempotencyCache {
            /** @var Repository $cache */
            $cache = $app->make('cache.store');

            return new IdempotencyCache($cache);
        });

        $this->app->afterResolving(Router::class, function (Router $router): void {
            $router->aliasMiddleware('idempotent', Idempotent::class);
        });
    }
}
