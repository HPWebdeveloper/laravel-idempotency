<?php

declare(strict_types=1);

namespace WendellAdriel\Idempotency\Support;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

final class RequestFingerprint
{
    public function storageKey(Request $request, string $scopePrefix, string $header, string $clientKey): string
    {
        return hash('xxh128', implode('|', [
            $this->routeIdentity($request),
            strtoupper($request->method()),
            $scopePrefix,
            $header,
            $clientKey,
        ]));
    }

    public function fingerprint(Request $request): string
    {
        return hash('xxh128', implode('|', [
            strtoupper($request->method()),
            $this->routeIdentity($request),
            $request->getQueryString() ?? '',
            $this->hashPayload($request),
            $request->getContentTypeFormat() ?? '',
        ]));
    }

    public function routeIdentity(Request $request): string
    {
        /** @var Route|null $route */
        $route = $request->route();

        return match (true) {
            $route === null => $request->getPathInfo(),
            is_string($route->getName()) => $route->getName(),
            default => ($route->getDomain() ?? '') . '/' . $route->uri(),
        };
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
}
