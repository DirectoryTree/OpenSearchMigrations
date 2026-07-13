<?php

namespace DirectoryTree\OpenSearchMigrations\Deployments;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use stdClass;

class Deployment
{
    /**
     * Create a deployment with a provisioned candidate index.
     */
    public static function provisioned(
        string $name,
        string $alias,
        string $activeIndex,
        string $candidateIndex,
        CarbonInterface $now,
    ): static {
        $now = CarbonImmutable::instance($now);

        return new static(
            id: null,
            name: $name,
            alias: $alias,
            activeIndex: $activeIndex,
            candidateIndex: $candidateIndex,
            previousIndex: null,
            status: DeploymentStatus::Provisioned,
            readyAt: null,
            cutoverAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    /**
     * Create a deployment from a database record.
     */
    public static function fromRecord(stdClass $record): static
    {
        return new static(
            id: (int) $record->id,
            name: $record->name,
            alias: $record->alias,
            activeIndex: $record->active_index,
            candidateIndex: $record->candidate_index,
            previousIndex: $record->previous_index,
            status: DeploymentStatus::from($record->status),
            readyAt: isset($record->ready_at) ? CarbonImmutable::parse($record->ready_at) : null,
            cutoverAt: isset($record->cutover_at) ? CarbonImmutable::parse($record->cutover_at) : null,
            createdAt: CarbonImmutable::parse($record->created_at),
            updatedAt: CarbonImmutable::parse($record->updated_at),
        );
    }

    /**
     * Create a new deployment value object.
     */
    protected function __construct(
        public ?int $id,
        public string $name,
        public string $alias,
        public string $activeIndex,
        public ?string $candidateIndex,
        public ?string $previousIndex,
        public DeploymentStatus $status,
        public ?CarbonImmutable $readyAt,
        public ?CarbonImmutable $cutoverAt,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    /**
     * Begin backfilling the candidate index.
     */
    public function beginBackfill(): static
    {
        return $this->transition(
            activeIndex: $this->activeIndex,
            candidateIndex: $this->candidateIndex,
            previousIndex: $this->previousIndex,
            status: DeploymentStatus::Backfilling,
            readyAt: $this->readyAt,
            cutoverAt: $this->cutoverAt,
        );
    }

    /**
     * Mark the candidate index as ready for cutover.
     */
    public function markReady(CarbonInterface $now): static
    {
        return $this->transition(
            activeIndex: $this->activeIndex,
            candidateIndex: $this->candidateIndex,
            previousIndex: $this->previousIndex,
            status: DeploymentStatus::Ready,
            readyAt: CarbonImmutable::instance($now),
            cutoverAt: $this->cutoverAt,
        );
    }

    /**
     * Preserve the active index while an alias cutover is in progress.
     */
    public function stageCutover(): static
    {
        return $this->transition(
            activeIndex: $this->activeIndex,
            candidateIndex: $this->candidateIndex,
            previousIndex: $this->activeIndex,
            status: $this->status,
            readyAt: $this->readyAt,
            cutoverAt: $this->cutoverAt,
        );
    }

    /**
     * Promote the candidate index after its alias cutover.
     */
    public function completeCutover(CarbonInterface $now): static
    {
        return $this->transition(
            activeIndex: $this->candidateIndex,
            candidateIndex: null,
            previousIndex: $this->previousIndex,
            status: DeploymentStatus::Active,
            readyAt: $this->readyAt,
            cutoverAt: CarbonImmutable::instance($now),
        );
    }

    /**
     * Preserve the active index while an alias rollback is in progress.
     */
    public function stageRollback(): static
    {
        return $this->transition(
            activeIndex: $this->activeIndex,
            candidateIndex: $this->activeIndex,
            previousIndex: $this->previousIndex,
            status: $this->status,
            readyAt: $this->readyAt,
            cutoverAt: $this->cutoverAt,
        );
    }

    /**
     * Restore the previous index after its alias rollback.
     */
    public function completeRollback(CarbonInterface $now): static
    {
        return $this->transition(
            activeIndex: $this->previousIndex,
            candidateIndex: null,
            previousIndex: $this->activeIndex,
            status: DeploymentStatus::Active,
            readyAt: $this->readyAt,
            cutoverAt: CarbonImmutable::instance($now),
        );
    }

    /**
     * Return to the active index without a candidate.
     */
    public function cancelCandidate(): static
    {
        return $this->transition(
            activeIndex: $this->activeIndex,
            candidateIndex: null,
            previousIndex: null,
            status: DeploymentStatus::Active,
            readyAt: null,
            cutoverAt: $this->cutoverAt,
        );
    }

    /**
     * Complete the rollback window without a previous index.
     */
    public function retirePrevious(): static
    {
        return $this->transition(
            activeIndex: $this->activeIndex,
            candidateIndex: $this->candidateIndex,
            previousIndex: null,
            status: $this->status,
            readyAt: $this->readyAt,
            cutoverAt: $this->cutoverAt,
        );
    }

    /**
     * Get every index that should receive writes during this deployment.
     *
     * @return string[]
     */
    public function writeIndexes(): array
    {
        $candidate = in_array($this->status, [
            DeploymentStatus::Backfilling,
            DeploymentStatus::Ready,
        ], true) ? $this->candidateIndex : null;

        return array_values(array_unique(array_filter([
            $this->alias,
            $candidate,
            $this->previousIndex,
        ])));
    }

    /**
     * Create a new deployment containing the given lifecycle state.
     */
    protected function transition(
        string $activeIndex,
        ?string $candidateIndex,
        ?string $previousIndex,
        DeploymentStatus $status,
        ?CarbonImmutable $readyAt,
        ?CarbonImmutable $cutoverAt,
    ): static {
        return new static(
            id: $this->id,
            name: $this->name,
            alias: $this->alias,
            activeIndex: $activeIndex,
            candidateIndex: $candidateIndex,
            previousIndex: $previousIndex,
            status: $status,
            readyAt: $readyAt,
            cutoverAt: $cutoverAt,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }
}
