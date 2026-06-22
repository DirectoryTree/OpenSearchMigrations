<?php

namespace DirectoryTree\OpenSearchMigrations\Filesystem;

/**
 * Represents an OpenSearch migration file.
 */
class MigrationFile
{
    /**
     * Create a new migration file instance.
     */
    public function __construct(
        protected string $filePath
    ) {}

    /**
     * Get the migration file name without its extension.
     */
    public function name(): string
    {
        return basename($this->filePath, '.php');
    }

    /**
     * Get the full migration file path.
     */
    public function path(): string
    {
        return $this->filePath;
    }
}
