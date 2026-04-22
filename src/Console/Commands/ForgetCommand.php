<?php

declare(strict_types=1);

namespace WendellAdriel\Idempotency\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository;
use WendellAdriel\Idempotency\Enums\IdempotencyScope;
use WendellAdriel\Idempotency\Support\IdempotencyCache;
use WendellAdriel\Idempotency\Support\IdempotencyIndex;

final class ForgetCommand extends Command
{
    protected $signature = 'idempotency:forget
        {--all : Remove every idempotent entry}
        {--scope= : Limit removal to a scope: user, ip, or global}
        {--id= : Identifier for user or ip scope}
        {--key= : Client idempotency key to remove across all scopes}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Remove cached idempotent entries by scope, identifier, client key, or wholesale.';

    public function handle(
        IdempotencyIndex $index,
        IdempotencyCache $idempotencyCache,
        Repository $cache,
    ): int {
        $all = (bool) $this->option('all');
        $rawScope = $this->option('scope');
        $identifier = $this->option('id');
        $key = $this->option('key');

        $hasScope = is_string($rawScope) && $rawScope !== '';
        $hasKey = is_string($key) && $key !== '';
        $modes = ($all ? 1 : 0) + ($hasScope ? 1 : 0) + ($hasKey ? 1 : 0);

        if ($modes === 0) {
            $this->error('You must pass one of --all, --scope, or --key.');

            return self::FAILURE;
        }

        if ($modes > 1) {
            $this->error('The --all, --scope, and --key options are mutually exclusive.');

            return self::FAILURE;
        }

        $scope = null;
        if ($hasScope) {
            $scope = IdempotencyScope::tryFrom($rawScope);

            if ($scope === null) {
                $this->error(sprintf('Unsupported scope [%s]. Use user, ip, or global.', $rawScope));

                return self::FAILURE;
            }

            if ($scope !== IdempotencyScope::Global && (! is_string($identifier) || $identifier === '')) {
                $this->error('The --id option is required when using --scope=user or --scope=ip.');

                return self::FAILURE;
            }
        }

        if ($all && ! (bool) $this->option('force') && ! $this->confirm('Are you sure you want to remove all idempotent entries?')) {
            $this->line('Aborted.');

            return self::SUCCESS;
        }

        $storageKeys = match (true) {
            $all => $index->flush(),
            $hasKey => $index->forgetByClientKey((string) $key),
            $scope === IdempotencyScope::Global => $index->forget(IdempotencyScope::Global, ''),
            $scope instanceof IdempotencyScope => $index->forget($scope, (string) $identifier),
            default => [],
        };

        foreach ($storageKeys as $storageKey) {
            $cache->forget($idempotencyCache->responseKey($storageKey));
            $cache->forget($idempotencyCache->lockKey($storageKey));
        }

        $this->line(sprintf('Removed %d idempotent entries.', count($storageKeys)));

        return self::SUCCESS;
    }
}
