<?php

declare(strict_types=1);

namespace WendellAdriel\Idempotency\Support;

use WendellAdriel\Idempotency\Enums\IdempotencyScope;

final readonly class IndexMember
{
    public function __construct(
        public string $storageKey,
        public IdempotencyScope $scope,
        public string $identifier,
        public string $clientKey,
        public string $route,
        public string $method,
        public int $status,
        public int $createdAt,
        public int $expiresAt,
    ) {}

    /**
     * @param  array<mixed, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $scope = $data['scope'] ?? null;

        return new self(
            storageKey: is_string($data['storageKey'] ?? null) ? $data['storageKey'] : '',
            scope: $scope instanceof IdempotencyScope
                ? $scope
                : (is_string($scope) ? (IdempotencyScope::tryFrom($scope) ?? IdempotencyScope::Global) : IdempotencyScope::Global),
            identifier: is_string($data['identifier'] ?? null) ? $data['identifier'] : '',
            clientKey: is_string($data['clientKey'] ?? null) ? $data['clientKey'] : '',
            route: is_string($data['route'] ?? null) ? $data['route'] : '',
            method: is_string($data['method'] ?? null) ? $data['method'] : '',
            status: is_int($data['status'] ?? null) ? $data['status'] : 0,
            createdAt: is_int($data['createdAt'] ?? null) ? $data['createdAt'] : 0,
            expiresAt: is_int($data['expiresAt'] ?? null) ? $data['expiresAt'] : 0,
        );
    }

    /**
     * @return array{
     *     storageKey: string,
     *     scope: string,
     *     identifier: string,
     *     clientKey: string,
     *     route: string,
     *     method: string,
     *     status: int,
     *     createdAt: int,
     *     expiresAt: int,
     * }
     */
    public function toArray(): array
    {
        return [
            'storageKey' => $this->storageKey,
            'scope' => $this->scope->value,
            'identifier' => $this->identifier,
            'clientKey' => $this->clientKey,
            'route' => $this->route,
            'method' => $this->method,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
            'expiresAt' => $this->expiresAt,
        ];
    }
}
