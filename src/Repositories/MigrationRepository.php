<?php

namespace DirectoryTree\OpenSearchMigrations\Repositories;

use DirectoryTree\OpenSearchMigrations\ReadinessInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

/**
 * Stores and retrieves executed OpenSearch migration records.
 */
class MigrationRepository implements ReadinessInterface
{
    /**
     * Create a new migration repository instance.
     */
    public function __construct(
        protected string $table,
        protected ?string $connection = null
    ) {}

    /**
     * Insert an executed migration record.
     */
    public function insert(string $fileName, int $batch): bool
    {
        return $this->table()->insert([
            'migration' => $fileName,
            'batch' => $batch,
        ]);
    }

    /**
     * Determine if a migration has been executed.
     */
    public function exists(string $fileName): bool
    {
        return $this->table()
            ->where('migration', $fileName)
            ->exists();
    }

    /**
     * Delete an executed migration record.
     */
    public function delete(string $fileName): bool
    {
        return (bool) $this->table()
            ->where('migration', $fileName)
            ->delete();
    }

    /**
     * Delete all executed migration records.
     */
    public function deleteAll(): void
    {
        $this->table()->delete();
    }

    /**
     * Truncate all executed migration records.
     *
     * @deprecated
     */
    public function truncate(): void
    {
        $this->table()->truncate();
    }

    /**
     * Create the migration repository table.
     */
    public function create(): void
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->string('migration');
            $table->integer('batch');
        });
    }

    /**
     * Get the latest migration batch number.
     */
    public function getLastBatchNumber(): ?int
    {
        /** @var stdClass|null $record */
        $record = $this->table()
            ->select('batch')
            ->orderBy('batch', 'desc')
            ->first();

        return isset($record) ? (int) $record->batch : null;
    }

    /**
     * Get all migration names from the latest batch.
     *
     * @return Collection<int, string>
     */
    public function getLastBatch(): Collection
    {
        return $this->table()
            ->where('batch', $this->getLastBatchNumber())
            ->orderBy('migration', 'desc')
            ->pluck('migration');
    }

    /**
     * Get all executed migration names.
     *
     * @return Collection<int, string>
     */
    public function getAll(): Collection
    {
        return $this->table()
            ->orderBy('migration', 'desc')
            ->pluck('migration');
    }

    /**
     * Get a query builder for the migration table.
     */
    protected function table(): Builder
    {
        return DB::connection($this->connection)->table($this->table);
    }

    /**
     * Determine if the migration repository is ready.
     */
    public function isReady(): bool
    {
        if (Schema::connection($this->connection)->hasTable($this->table)) {
            return true;
        }

        $this->create();

        return true;
    }
}
