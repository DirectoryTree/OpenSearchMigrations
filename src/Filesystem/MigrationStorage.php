<?php

namespace DirectoryTree\OpenSearchMigrations\Filesystem;

use DirectoryTree\OpenSearchMigrations\ReadinessInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

/**
 * Stores and retrieves OpenSearch migration files.
 */
class MigrationStorage implements ReadinessInterface
{
    /**
     * @var string
     */
    protected $directory;

    /**
     * Create a new migration storage instance.
     */
    public function __construct(
        protected Filesystem $filesystem
    ) {
        $this->directory = rtrim(config('opensearch-migrations.storage_directory', ''), '/');
    }

    /**
     * Create a migration file.
     */
    public function create(string $fileName, string $content): MigrationFile
    {
        if (! $this->filesystem->isDirectory($this->directory)) {
            $this->filesystem->makeDirectory($this->directory, 0755, true);
        }

        $filePath = $this->resolvePath($fileName);
        $this->filesystem->put($filePath, $content);

        return new MigrationFile($filePath);
    }

    /**
     * Find a migration file by name.
     */
    public function findByName(string $fileName): ?MigrationFile
    {
        $filePath = $this->resolvePath($fileName);

        return $this->filesystem->exists($filePath) ? new MigrationFile($filePath) : null;
    }

    /**
     * Find all migration files in storage.
     *
     * @return Collection<int, MigrationFile>
     */
    public function findAll(): Collection
    {
        $files = $this->filesystem->glob($this->directory.'/*_*.php');

        return collect($files)->sort()->map(function (string $filePath) {
            return new MigrationFile($filePath);
        });
    }

    /**
     * Resolve a migration file name into a path.
     */
    protected function resolvePath(string $fileName): string
    {
        return sprintf('%s/%s.php', $this->directory, str_replace('.php', '', trim($fileName)));
    }

    /**
     * Determine if the migration storage directory exists.
     */
    public function isReady(): bool
    {
        return $this->filesystem->isDirectory($this->directory);
    }
}
