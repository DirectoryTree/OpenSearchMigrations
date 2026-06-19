<?php

namespace DirectoryTree\OpenSearchMigrations\Filesystem;

class MigrationFile
{
    /**
     * @var string
     */
    protected $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function getName(): string
    {
        return basename($this->filePath, '.php');
    }

    public function getPath(): string
    {
        return $this->filePath;
    }
}
