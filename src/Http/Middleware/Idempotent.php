<?php

declare(strict_types=1);

namespace WendellAdriel\Idempotency\Http\Middleware;

use Closure;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use WendellAdriel\Idempotency\Enums\IdempotencyScope;
use WendellAdriel\Idempotency\Support\IdempotencyCache;
use WendellAdriel\Idempotency\Support\IdempotencyOptions;
use WendellAdriel\Idempotency\Support\RequestFingerprint;
use WendellAdriel\Idempotency\Support\ScopeResolver;
use WendellAdriel\Idempotency\Support\StoredResponse;

final readonly class Idempotent
{
    public function __construct(
        private Repository $cache,
        private IdempotencyCache $idempotencyCache,
        private ScopeResolver $scopeResolver,
        private RequestFingerprint $fingerprint,
    ) {}

    public static function using(
        ?int $ttl = null,
        ?bool $required = null,
        ?IdempotencyScope $scope = null,
        ?string $header = null,
    ): string {
        return self::class . ':' . IdempotencyOptions::resolve(
            ttl: $ttl,
            required: $required,
            scope: $scope,
            header: $header,
        )->serialize();
    }

    public function handle(
        Request $request,
        Closure $next,
        null|int|string $ttl = null,
        null|bool|string $required = null,
        null|string|IdempotencyScope $scope = null,
        ?string $header = null,
    ): SymfonyResponse {
        if (! $this->isIdempotentMethod($request)) {
            return $next($request);
        }

        $options = IdempotencyOptions::resolve($ttl, $required, $scope, $header);
        $clientKey = $request->header($options->header);

        if (! is_string($clientKey) || $clientKey === '') {
            if ($options->required) {
                throw new HttpException(Response::HTTP_BAD_REQUEST, sprintf('Missing required header: %s', $options->header));
            }

            return $next($request);
        }

        $scopePrefix = $this->scopeResolver->resolve($request, $options->scope);
        $storageKey = $this->fingerprint->storageKey($request, $scopePrefix, $options->header, $clientKey);
        $fingerprint = $this->fingerprint->fingerprint($request);
        $stored = $this->idempotencyCache->get($storageKey);

        if ($stored instanceof StoredResponse) {
            if ($stored->fingerprint !== $fingerprint) {
                throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Idempotency key already used with different request parameters.');
            }

            return $stored->toResponse();
        }

        $store = $this->cache->getStore();

        if (! $store instanceof LockProvider) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'The configured cache store does not support atomic locks.');
        }

        $lock = $store->lock($this->idempotencyCache->lockKey($storageKey), 10);

        if (! $lock->get()) {
            throw new HttpException(Response::HTTP_CONFLICT, 'A request with this idempotency key is currently being processed.', null, ['Retry-After' => '1']);
        }

        $request->attributes->set('idempotent', true);
        $request->attributes->set('idempotency-key', $clientKey);

        try {
            /** @var SymfonyResponse $response */
            $response = $next($request);

            $this->idempotencyCache->put(
                $storageKey,
                $this->idempotencyCache->serializeResponse($response, $fingerprint),
                $options->ttl,
            );

            return $response;
        } finally {
            $lock->release();
        }
    }

    private function isIdempotentMethod(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH'], true);
    }
}
