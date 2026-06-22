<?php

use DirectoryTree\OpenSearchMigrations\Filesystem\MigrationFile;

const FULL_PATH = '/tmp/test.php';

it('gets the full migration file path', function (): void {
    expect((new MigrationFile(FULL_PATH))->path())->toBe(FULL_PATH);
});

it('gets the migration file name without its extension', function (): void {
    expect((new MigrationFile(FULL_PATH))->name())->toBe(basename(FULL_PATH, '.php'));
});
