<?php

declare(strict_types=1);

namespace WendellAdriel\Idempotency\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use WendellAdriel\Idempotency\Enums\IdempotencyScope;

final class ScopeResolver
{
    public function resolve(Request $request, IdempotencyScope $scope): string
    {
        [$resolvedScope, $identifier] = $this->describe($request, $scope);

        return $resolvedScope === IdempotencyScope::Global
            ? IdempotencyScope::Global->value
            : sprintf('%s:%s', $resolvedScope->value, $identifier);
    }

    /**
     * @return array{0: IdempotencyScope, 1: string}
     */
    public function describe(Request $request, IdempotencyScope $scope): array
    {
        return match ($scope) {
            IdempotencyScope::User => $this->describeUserScope($request),
            IdempotencyScope::Ip => [IdempotencyScope::Ip, (string) $request->ip()],
            IdempotencyScope::Global => [IdempotencyScope::Global, ''],
        };
    }

    /**
     * @return array{0: IdempotencyScope, 1: string}
     */
    private function describeUserScope(Request $request): array
    {
        $user = $request->user();
        $identifier = $user instanceof Authenticatable ? $user->getAuthIdentifier() : null;

        if (is_scalar($identifier)) {
            return [IdempotencyScope::User, (string) $identifier];
        }

        return [IdempotencyScope::Ip, (string) $request->ip()];
    }
}
