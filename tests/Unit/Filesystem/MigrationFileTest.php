<?php

namespace DirectoryTree\OpenSearchMigrations\Tests\Unit\Filesystem;

use DirectoryTree\OpenSearchMigrations\Filesystem\MigrationFile;
use PHPUnit\Framework\TestCase;

class MigrationFileTest extends TestCase
{
    protected const FULL_PATH = '/tmp/test.php';

    public function test_path_getter(): void
    {
        $this->assertSame(
            static::FULL_PATH,
            (new MigrationFile(static::FULL_PATH))->getPath()
        );
    }

    public function test_name_getter(): void
    {
        $this->assertSame(
            basename(static::FULL_PATH, '.php'),
            (new MigrationFile(static::FULL_PATH))->getName()
        );
    }
}
