<?php

declare(strict_types=1);

namespace WendellAdriel\Idempotency\Http\Middleware;

use Closure;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use WendellAdriel\Idempotency\Enums\IdempotencyScope;
use WendellAdriel\Idempotency\Support\IdempotencyCache;
use WendellAdriel\Idempotency\Support\StoredResponse;

final readonly class Idempotent
{
    public function __construct(
        private Repository $cache,
        private IdempotencyCache $idempotencyCache,
    ) {}

    public static function using(
        ?int $ttl = null,
        ?bool $required = null,
        ?IdempotencyScope $scope = null,
        ?string $header = null,
    ): string {
        $ttl ??= config()->integer('idempotency.ttl');
        $required ??= config()->boolean('idempotency.required');
        $scope = IdempotencyScope::fromConfig($scope);
        $header ??= config()->string('idempotency.header');

        return self::class . ':' . implode(',', [
            $ttl,
            $required ? '1' : '0',
            $scope->value,
            $header,
        ]);
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

        if (! is_int($ttl)) {
            $ttl = $ttl !== null
                ? (int) $ttl
                : config()->integer('idempotency.ttl');
        }

        if (! is_bool($required)) {
            $required = $required !== null
                ? filter_var($required, FILTER_VALIDATE_BOOLEAN)
                : config()->boolean('idempotency.required');
        }

        $scope = IdempotencyScope::fromConfig($scope);
        $header ??= config()->string('idempotency.header');

        $clientKey = $request->header($header);

        if (! is_string($clientKey) || $clientKey === '') {
            if ($required) {
                throw new HttpException(Response::HTTP_BAD_REQUEST, sprintf('Missing required header: %s', $header));
            }

            return $next($request);
        }

        $scopePrefix = $this->resolveScope($request, $scope);
        $routeIdentity = $this->resolveRouteIdentity($request);
        $storageKey = $this->buildStorageKey($routeIdentity, $request->method(), $scopePrefix, $header, $clientKey);
        $fingerprint = $this->buildFingerprint($request, $routeIdentity);
        $stored = $this->idempotencyCache->get($storageKey);

        if ($stored instanceof StoredResponse) {
            if ($stored->fingerprint !== $fingerprint) {
                throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Idempotency key already used with different request parameters.');
            }

            return $this->replayResponse($stored);
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
                $ttl,
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

    private function resolveScope(Request $request, IdempotencyScope $scope): string
    {
        return match ($scope) {
            IdempotencyScope::User => $this->resolveUserScope($request),
            IdempotencyScope::Ip => 'ip:' . $request->ip(),
            IdempotencyScope::Global => 'global',
        };
    }

    private function resolveUserScope(Request $request): string
    {
        $user = $request->user();
        $identifier = $user instanceof Authenticatable ? $user->getAuthIdentifier() : null;

        return is_scalar($identifier)
            ? sprintf('user:%s', $identifier)
            : 'ip:' . $request->ip();
    }

    private function resolveRouteIdentity(Request $request): string
    {
        /** @var Route|null $route */
        $route = $request->route();

        return match (true) {
            $route === null => $request->getPathInfo(),
            is_string($route->getName()) => $route->getName(),
            default => ($route->getDomain() ?? '') . '/' . $route->uri(),
        };
    }

    private function buildStorageKey(string $routeIdentity, string $method, string $scopePrefix, string $header, string $clientKey): string
    {
        return hash('xxh128', implode('|', [
            $routeIdentity,
            strtoupper($method),
            $scopePrefix,
            $header,
            $clientKey,
        ]));
    }

    private function buildFingerprint(Request $request, string $routeIdentity): string
    {
        return hash('xxh128', implode('|', [
            strtoupper($request->method()),
            $routeIdentity,
            $request->getQueryString() ?? '',
            $this->hashPayload($request),
            $request->getContentTypeFormat() ?? '',
        ]));
    }

    private function hashPayload(Request $request): string
    {
        if ($request->isJson()) {
            $decoded = json_decode($request->getContent(), true);

            if (is_array($decoded)) {
                $this->recursiveKeySort($decoded);

                return hash('xxh128', (string) json_encode($decoded));
            }
        }

        return hash('xxh128', $request->getContent());
    }

    /**
     * @param  array<int|string, mixed>  $array
     */
    private function recursiveKeySort(array &$array): void
    {
        ksort($array);

        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveKeySort($value);
            }
        }
    }

    private function replayResponse(StoredResponse $stored): SymfonyResponse
    {
        if ($stored->isRedirect) {
            $response = new RedirectResponse(
                $stored->targetUrl ?? '/',
                $stored->status,
                $stored->headers,
            );
        } else {
            $response = new Response(
                $stored->content,
                $stored->status,
                $stored->headers,
            );
        }

        $response->headers->set('Idempotency-Replayed', 'true');

        return $response;
    }
}
