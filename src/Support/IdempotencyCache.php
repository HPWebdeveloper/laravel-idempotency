<?php

declare(strict_types=1);

namespace WendellAdriel\Idempotency\Support;

use Illuminate\Cache\Repository;
use Symfony\Component\HttpFoundation\Response;

final readonly class IdempotencyCache
{
    public function __construct(
        private Repository $cache,
    ) {}

    public function responseKey(string $storageKey): string
    {
        return 'idempotent-response:' . $storageKey;
    }

    public function lockKey(string $storageKey): string
    {
        return 'idempotent-lock:' . $storageKey;
    }

    public function get(string $storageKey): ?StoredResponse
    {
        $stored = $this->cache->get($this->responseKey($storageKey));

        if (! is_array($stored)) {
            return null;
        }

        $headers = is_array($stored['headers'] ?? null) ? $stored['headers'] : [];

        return StoredResponse::fromStored($stored, $this->normalizeHeaders($headers));
    }

    public function put(string $storageKey, StoredResponse $response, int $ttl): void
    {
        $this->cache->put($this->responseKey($storageKey), $response->toArray(), $ttl);
    }

    public function serializeResponse(Response $response, string $fingerprint): StoredResponse
    {
        /** @var array<string, list<string>> $headers */
        $headers = $this->serializableHeaders($response);

        return StoredResponse::fromResponse($response, $fingerprint, $headers);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function serializableHeaders(Response $response): array
    {
        return $this->normalizeHeaders($response->headers->all());
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, list<string>>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        unset($headers['date'], $headers['set-cookie']);

        foreach ($headers as $name => $values) {
            if (! is_array($values)) {
                continue;
            }

            $filtered = array_values(array_filter($values, is_string(...)));

            /** @var list<string> $filtered */
            $normalized[$name] = $filtered;
        }

        return $normalized;
    }
}
