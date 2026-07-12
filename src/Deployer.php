<?php

namespace DirectoryTree\OpenSearchMigrations;

use DirectoryTree\OpenSearchAdapter\Indices\IndexManagerInterface as AdapterIndexManagerInterface;
use DirectoryTree\OpenSearchClient\OpenSearchManager;
use DirectoryTree\OpenSearchMigrations\Deployments\Deployment;
use DirectoryTree\OpenSearchMigrations\Deployments\DeploymentException;
use DirectoryTree\OpenSearchMigrations\Deployments\DeploymentStatus;
use DirectoryTree\OpenSearchMigrations\Repositories\DeploymentRepository;
use DirectoryTree\OpenSearchMigrations\Support\MigrationPrefix;
use Illuminate\Support\Facades\Date;
use Throwable;

class Deployer
{
    /**
     * Create a new deployer instance.
     */
    public function __construct(
        protected DeploymentRepository $deployments,
        protected IndexManagerInterface $indexes,
        protected AdapterIndexManagerInterface $adapterIndexes,
        protected OpenSearchManager $openSearch,
    ) {}

    /**
     * Create a candidate index and begin a deployment.
     */
    public function start(
        string $name,
        string $alias,
        ?callable $configure = null,
        ?string $version = null,
    ): Deployment {
        $this->deployments->prepare();

        $existing = $this->deployments->find($name);

        if ($existing?->candidateIndex || $existing?->previousIndex) {
            throw new DeploymentException('The current deployment must be completed or retired before starting another.');
        }

        $prefixedAlias = MigrationPrefix::alias($alias);
        $activeIndex = $this->resolveAliasIndex($prefixedAlias);
        $candidate = sprintf('%s_v%s', $name, $version ?? Date::now()->format('YmdHisv'));

        $this->indexes->create($candidate, $configure);

        return $this->deployments->save(
            Deployment::backfilling(
                name: $name,
                alias: $prefixedAlias,
                activeIndex: $activeIndex,
                candidateIndex: MigrationPrefix::index($candidate),
                now: Date::now(),
            )
        );
    }

    /**
     * Mark a candidate index as ready for cutover.
     */
    public function markReady(string $name): Deployment
    {
        $deployment = $this->deployments->findOrFail($name);

        if (! $deployment->candidateIndex) {
            throw new DeploymentException('The deployment does not have a candidate index to mark as ready.');
        }

        return $this->deployments->save(
            $deployment->markReady(Date::now())
        );
    }

    /**
     * Atomically switch the live alias to a ready candidate index.
     */
    public function cutover(string $name): Deployment
    {
        $deployment = $this->deployments->findOrFail($name);

        if ($deployment->status !== DeploymentStatus::Ready || ! $deployment->candidateIndex) {
            throw new DeploymentException('The candidate index must be ready before cutover.');
        }

        $deployment = $this->deployments->save(
            $deployment->stageCutover()
        );

        $this->moveAlias(
            $deployment->alias,
            $deployment->activeIndex,
            $deployment->candidateIndex
        );

        return $this->deployments->save(
            $deployment->completeCutover(Date::now())
        );
    }

    /**
     * Atomically restore the previous physical index.
     */
    public function rollback(string $name): Deployment
    {
        $deployment = $this->deployments->findOrFail($name);

        if (! $deployment->previousIndex) {
            throw new DeploymentException('The deployment does not have a previous index to restore.');
        }

        $deployment = $this->deployments->save(
            $deployment->stageRollback()
        );

        $this->moveAlias(
            $deployment->alias,
            $deployment->activeIndex,
            $deployment->previousIndex
        );

        return $this->deployments->save(
            $deployment->completeRollback(Date::now())
        );
    }

    /**
     * Delete a candidate index and return to the active deployment.
     */
    public function cancel(string $name): Deployment
    {
        $deployment = $this->deployments->findOrFail($name);

        if (! $deployment->candidateIndex) {
            throw new DeploymentException('The deployment does not have a candidate index to cancel.');
        }

        if ($this->resolveAliasIndex($deployment->alias) !== $deployment->activeIndex) {
            throw new DeploymentException('The candidate index is already active and cannot be cancelled.');
        }

        $candidate = $deployment->candidateIndex;

        $this->deployments->save(
            $deployment->cancelCandidate()
        );

        try {
            $this->adapterIndexes->delete($candidate);
        } catch (Throwable $exception) {
            $this->deployments->save($deployment);

            throw $exception;
        }

        return $this->deployments->findOrFail($name);
    }

    /**
     * Delete the previous physical index and complete a deployment.
     */
    public function retire(string $name): Deployment
    {
        $deployment = $this->deployments->findOrFail($name);

        if (! $deployment->previousIndex) {
            throw new DeploymentException('The deployment does not have a previous index to retire.');
        }

        $previous = $deployment->previousIndex;

        $this->deployments->save(
            $deployment->retirePrevious()
        );

        try {
            $this->adapterIndexes->delete($previous);
        } catch (Throwable $exception) {
            $this->deployments->save($deployment);

            throw $exception;
        }

        return $this->deployments->findOrFail($name);
    }

    /**
     * Get a deployment by its logical index name.
     */
    public function find(string $name): ?Deployment
    {
        $this->deployments->prepare();

        return $this->deployments->find($name);
    }

    /**
     * Resolve the single physical index behind an alias.
     */
    protected function resolveAliasIndex(string $alias): string
    {
        $indexes = array_keys(
            $this->openSearch->default()
                ->indices()
                ->getAlias(['name' => $alias])
        );

        if (count($indexes) !== 1) {
            throw new DeploymentException("Alias [{$alias}] must point to exactly one index.");
        }

        return $indexes[0];
    }

    /**
     * Atomically move an alias between physical indexes.
     */
    protected function moveAlias(string $alias, string $from, string $to): void
    {
        $current = $this->resolveAliasIndex($alias);

        if ($current === $to) {
            return;
        }

        if ($current !== $from) {
            throw new DeploymentException("Alias [{$alias}] points to unexpected index [{$current}].");
        }

        $this->openSearch->default()->indices()->updateAliases([
            'body' => [
                'actions' => [
                    ['remove' => ['index' => $from, 'alias' => $alias]],
                    ['add' => ['index' => $to, 'alias' => $alias, 'is_write_index' => true]],
                ],
            ],
        ]);
    }
}
