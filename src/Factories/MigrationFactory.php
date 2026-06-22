<?php

namespace DirectoryTree\OpenSearchMigrations\Factories;

use DirectoryTree\OpenSearchMigrations\Filesystem\MigrationFile;
use DirectoryTree\OpenSearchMigrations\MigrationInterface;
use Illuminate\Support\Str;

/**
 * Creates migration instances from migration files.
 */
class MigrationFactory
{
    /**
     * Make a migration instance from a migration file.
     */
    public function makeFromFile(MigrationFile $file): MigrationInterface
    {
        require_once $file->path();

        $className = Str::studly(implode('_', array_slice(explode('_', $file->name()), 4)));

        return resolve($className);
    }
}
