<?php

use DirectoryTree\OpenSearchMigrations\Filesystem\MigrationFile;
use DirectoryTree\OpenSearchMigrations\Filesystem\MigrationStorage;

it('creates files', function (): void {
    $storage = resolve(MigrationStorage::class);
    $fileName = uniqid();

    $file = $storage->create($fileName, 'content');

    expect($file->name())->toBe($fileName);
    expect($file->path())->toBeFile();
    expect(file_get_contents($file->path()))->toBe('content');

    @unlink($file->path());
});

it('creates the directory along with the file', function (): void {
    $directory = config('opensearch-migrations.storage_directory');
    $firstLevelDirectory = $directory.'/nested';
    $secondLevelDirectory = $firstLevelDirectory.'/directories';

    config()->set('opensearch-migrations.storage_directory', $secondLevelDirectory);

    $storage = resolve(MigrationStorage::class);
    $file = $storage->create('2019_12_12_201657_test_index', 'content');

    expect($secondLevelDirectory)->toBeDirectory();

    @unlink($file->path());
    @rmdir($secondLevelDirectory);
    @rmdir($firstLevelDirectory);
});

it('prepares when the directory exists', function (): void {
    $directory = config('opensearch-migrations.storage_directory');

    resolve(MigrationStorage::class)->prepare();

    expect($directory)->toBeDirectory();
});

it('creates the directory when preparing', function (): void {
    $directory = sys_get_temp_dir().'/opensearch_migrations_missing_storage';

    config()->set('opensearch-migrations.storage_directory', $directory);

    resolve(MigrationStorage::class)->prepare();

    expect($directory)->toBeDirectory();

    @rmdir($directory);
});

it('finds existing files', function (string $fileName): void {
    $file = resolve(MigrationStorage::class)->find($fileName);

    expect($file)->toBeInstanceOf(MigrationFile::class);
    expect($file->name())->toBe(basename(trim($fileName), '.php'));
})->with([
    ['2018_12_01_081000_create_test_index'],
    ['2018_12_01_081000_create_test_index.php'],
    ['2019_08_10_142230_update_test_index_mapping'],
    ['2019_08_10_142230_update_test_index_mapping.php'],
]);

it('does not find missing files', function (string $fileName): void {
    expect(resolve(MigrationStorage::class)->find($fileName))->toBeNull();
})->with([
    ['2020_12_01_081000_create_test_index'],
    ['2020_12_01_081000_create_test_index.php'],
    ['2020_08_10_142230_update_test_index_mapping'],
    ['2020_08_10_142230_update_test_index_mapping.php'],
]);

it('retrieves all migration files', function (): void {
    $files = resolve(MigrationStorage::class)->all();

    expect($files->map(fn (MigrationFile $file) => $file->name())->toArray())->toBe([
        '2018_12_01_081000_create_test_index',
        '2019_08_10_142230_update_test_index_mapping',
    ]);
});
