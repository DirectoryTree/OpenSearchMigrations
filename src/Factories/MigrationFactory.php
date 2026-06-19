<?php

namespace DirectoryTree\OpenSearchMigrations\Factories;

use DirectoryTree\OpenSearchMigrations\Filesystem\MigrationFile;
use DirectoryTree\OpenSearchMigrations\MigrationInterface;
use Illuminate\Support\Str;

class MigrationFactory
{
    public function makeFromFile(MigrationFile $file): MigrationInterface
    {
        require_once $file->getPath();

        $className = Str::studly(implode('_', array_slice(explode('_', $file->getName()), 4)));

        return resolve($className);
    }
}
