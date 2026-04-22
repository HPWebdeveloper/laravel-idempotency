<?php

declare(strict_types=1);

namespace WendellAdriel\Idempotency\Console\Commands;

use Carbon\CarbonInterval;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use WendellAdriel\Idempotency\Enums\IdempotencyScope;
use WendellAdriel\Idempotency\Support\IdempotencyIndex;
use WendellAdriel\Idempotency\Support\IndexMember;

final class ListCommand extends Command
{
    protected $signature = 'idempotency:list
        {--scope= : Filter by scope: user, ip, or global}
        {--id= : Filter by identifier within a scope}
        {--limit=50 : Maximum number of rows to display}';

    protected $description = 'List cached idempotent entries with basic metadata.';

    public function handle(IdempotencyIndex $index): int
    {
        $rawScope = $this->option('scope');
        $identifier = $this->option('id');
        $limit = (int) $this->option('limit');

        $scope = null;
        if (is_string($rawScope) && $rawScope !== '') {
            $scope = IdempotencyScope::tryFrom($rawScope);

            if ($scope === null) {
                $this->error(sprintf('Unsupported scope [%s]. Use user, ip, or global.', $rawScope));

                return self::FAILURE;
            }
        }

        if ($limit <= 0) {
            $this->line('Nothing to display.');

            return self::SUCCESS;
        }

        $members = $this->loadMembers($scope, is_string($identifier) ? $identifier : null, $index);

        if ($members === []) {
            $this->line('No idempotent entries cached.');

            return self::SUCCESS;
        }

        usort($members, static fn (IndexMember $a, IndexMember $b): int => $b->createdAt <=> $a->createdAt);

        $total = count($members);
        $members = array_slice($members, 0, $limit);
        $now = Carbon::now()->getTimestamp();

        $rows = array_map(static fn (IndexMember $member): array => [
            $member->scope->value,
            $member->scope === IdempotencyScope::Global ? '—' : $member->identifier,
            $member->clientKey,
            $member->route,
            $member->method,
            (string) $member->status,
            Carbon::createFromTimestamp($member->createdAt)->format('Y-m-d H:i:s'),
            CarbonInterval::seconds(max(0, $member->expiresAt - $now))->cascade()->forHumans(['short' => true]),
        ], $members);

        $this->table(
            ['Scope', 'Identifier', 'Idempotency Key', 'Route', 'Method', 'Status', 'Created At', 'Expires In'],
            $rows
        );

        $this->line(sprintf('Showing %d of %d entries.', count($rows), $total));

        return self::SUCCESS;
    }

    /**
     * @return list<IndexMember>
     */
    private function loadMembers(?IdempotencyScope $scope, ?string $identifier, IdempotencyIndex $index): array
    {
        return match (true) {
            ! $scope instanceof IdempotencyScope => $index->all(),
            $scope === IdempotencyScope::Global => $index->forMember(IdempotencyScope::Global, ''),
            is_string($identifier) && $identifier !== '' => $index->forMember($scope, $identifier),
            default => array_values(array_filter(
                $index->all(),
                static fn (IndexMember $member): bool => $member->scope === $scope,
            )),
        };
    }
}
