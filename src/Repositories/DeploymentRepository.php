<?php

namespace DirectoryTree\OpenSearchMigrations\Repositories;

use DirectoryTree\OpenSearchMigrations\Deployments\Deployment;
use DirectoryTree\OpenSearchMigrations\Deployments\DeploymentException;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

class DeploymentRepository
{
    /**
     * Create a new deployment repository.
     */
    public function __construct(
        protected string $table,
        protected ?string $connection = null,
    ) {}

    /**
     * Create or replace a deployment record.
     */
    public function save(Deployment $deployment): Deployment
    {
        $this->table()->updateOrInsert(
            ['name' => $deployment->name],
            [
                'alias' => $deployment->alias,
                'active_index' => $deployment->activeIndex,
                'candidate_index' => $deployment->candidateIndex,
                'previous_index' => $deployment->previousIndex,
                'status' => $deployment->status->value,
                'ready_at' => $deployment->readyAt,
                'cutover_at' => $deployment->cutoverAt,
                'created_at' => $deployment->createdAt,
                'updated_at' => Date::now(),
            ],
        );

        return $this->findOrFail($deployment->name);
    }

    /**
     * Find a deployment by its logical name.
     */
    public function find(string $name): ?Deployment
    {
        $record = $this->table()->where('name', $name)->first();

        return $record ? Deployment::fromRecord($record) : null;
    }

    /**
     * Find a deployment or throw an exception.
     */
    public function findOrFail(string $name): Deployment
    {
        return $this->find($name)
            ?? throw new DeploymentException("OpenSearch deployment [{$name}] does not exist.");
    }

    /**
     * Get all deployments.
     *
     * @return Collection<int, Deployment>
     */
    public function all(): Collection
    {
        return $this->table()->orderBy('name')->get()->map(
            fn (stdClass $record) => Deployment::fromRecord($record)
        );
    }

    /**
     * Delete a deployment record.
     */
    public function delete(string $name): bool
    {
        return (bool) $this->table()->where('name', $name)->delete();
    }

    /**
     * Determine whether any managed deployments exist.
     */
    public function exists(): bool
    {
        return $this->table()->exists();
    }

    /**
     * Delete all deployment records.
     */
    public function deleteAll(): void
    {
        $this->table()->delete();
    }

    /**
     * Create the deployment repository table.
     */
    public function create(): void
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('alias')->unique();
            $table->string('active_index');
            $table->string('candidate_index')->nullable();
            $table->string('previous_index')->nullable();
            $table->string('status');
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('cutover_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Prepare the deployment repository table.
     */
    public function prepare(): void
    {
        if (! Schema::connection($this->connection)->hasTable($this->table)) {
            $this->create();
        }
    }

    /**
     * Get a query builder for the deployment table.
     */
    protected function table(): Builder
    {
        return DB::connection($this->connection)->table($this->table);
    }
}
