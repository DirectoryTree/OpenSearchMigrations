<?php

use DirectoryTree\OpenSearchMigrations\Factories\MigrationFactory;
use DirectoryTree\OpenSearchMigrations\Filesystem\MigrationStorage;
use DirectoryTree\OpenSearchMigrations\MigrationInterface;

it('creates migrations from files', function (string $fileName): void {
    $file = resolve(MigrationStorage::class)->find($fileName);

    expect(resolve(MigrationFactory::class)->makeFromFile($file))->toBeInstanceOf(MigrationInterface::class);
})->with([
    'create index migration' => ['2018_12_01_081000_create_test_index'],
    'update mapping migration' => ['2019_08_10_142230_update_test_index_mapping'],
]);
