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
}
