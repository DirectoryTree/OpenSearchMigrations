<?php

use DirectoryTree\OpenSearchMigrations\Facades\Index;
use DirectoryTree\OpenSearchMigrations\Migrator;
use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function seededMigrator(OutputStyle $output): array
{
    $table = config('opensearch-migrations.table');

    DB::table($table)->insert([
        ['migration' => '2018_12_01_081000_create_test_index', 'batch' => 1],
    ]);

    return [$table, resolve(Migrator::class)->setOutput($output)];
}

function outputExpectingLines(array $lines): OutputStyle
{
    $output = Mockery::mock(OutputStyle::class);

    foreach ($lines as $line) {
        $output->shouldReceive('writeln')->once()->ordered()->with($line);
    }

    return $output;
}

it('does not run a missing single migration', function (): void {
    $output = outputExpectingLines([
        '<error>Migration is not found:</error> 3020_11_01_045023_drop_test_index',
    ]);

    [, $migrator] = seededMigrator($output);

    expect($migrator->migrateOne('3020_11_01_045023_drop_test_index'))->toBe($migrator);
});

it('runs a single migration when it exists', function (): void {
    Index::shouldReceive('putMapping')->once();

    $output = outputExpectingLines([
        '<comment>Migrating:</comment> 2019_08_10_142230_update_test_index_mapping',
        '<info>Migrated:</info> 2019_08_10_142230_update_test_index_mapping',
    ]);

    [$table, $migrator] = seededMigrator($output);

    expect($migrator->migrateOne('2019_08_10_142230_update_test_index_mapping'))->toBe($migrator);
    expect(DB::table($table)->where([
        'migration' => '2019_08_10_142230_update_test_index_mapping',
        'batch' => 2,
    ])->exists())->toBeTrue();
});

it('does not run all migrations when the directory is empty', function (): void {
    $tmpDirectory = config('opensearch-migrations.storage_directory').'/tmp';

    @mkdir($tmpDirectory);
    config()->set('opensearch-migrations.storage_directory', $tmpDirectory);

    $output = outputExpectingLines(['<info>Nothing to migrate</info>']);

    seededMigrator($output);

    $migrator = resolve(Migrator::class)->setOutput($output);

    expect($migrator->migrateAll())->toBe($migrator);

    @rmdir($tmpDirectory);
});

it('runs all outstanding migrations', function (): void {
    Index::shouldReceive('putMapping')->once();

    $output = outputExpectingLines([
        '<comment>Migrating:</comment> 2019_08_10_142230_update_test_index_mapping',
        '<info>Migrated:</info> 2019_08_10_142230_update_test_index_mapping',
    ]);

    [$table, $migrator] = seededMigrator($output);

    expect($migrator->migrateAll())->toBe($migrator);
    expect(DB::table($table)->where([
        'migration' => '2019_08_10_142230_update_test_index_mapping',
        'batch' => 2,
    ])->exists())->toBeTrue();
});

it('does not roll back a missing single migration', function (): void {
    $output = outputExpectingLines([
        '<error>Migration is not found:</error> 3020_11_01_045023_drop_test_index',
    ]);

    [, $migrator] = seededMigrator($output);

    expect($migrator->rollbackOne('3020_11_01_045023_drop_test_index'))->toBe($migrator);
});

it('does not roll back a migration that has not run', function (): void {
    $output = outputExpectingLines([
        '<error>Migration is not yet migrated:</error> 2019_08_10_142230_update_test_index_mapping',
    ]);

    [, $migrator] = seededMigrator($output);

    expect($migrator->rollbackOne('2019_08_10_142230_update_test_index_mapping'))->toBe($migrator);
});

it('rolls back a migrated single migration', function (): void {
    Index::shouldReceive('drop')->once();

    $output = outputExpectingLines([
        '<comment>Rolling back:</comment> 2018_12_01_081000_create_test_index',
        '<info>Rolled back:</info> 2018_12_01_081000_create_test_index',
    ]);

    [$table, $migrator] = seededMigrator($output);

    expect($migrator->rollbackOne('2018_12_01_081000_create_test_index'))->toBe($migrator);
    expect(DB::table($table)->where([
        'migration' => '2018_12_01_081000_create_test_index',
        'batch' => 1,
    ])->exists())->toBeFalse();
});

it('does not roll back the last batch when some files are missing', function (): void {
    $output = outputExpectingLines([
        '<error>Migration is not found:</error> 2019_03_10_101500_create_test_index',
    ]);

    [$table, $migrator] = seededMigrator($output);

    DB::table($table)->insert([
        ['migration' => '2019_03_10_101500_create_test_index', 'batch' => 2],
    ]);

    expect($migrator->rollbackLastBatch())->toBe($migrator);
});

it('rolls back the last batch when all files are present', function (): void {
    Index::shouldReceive('putMapping')->once();

    $output = outputExpectingLines([
        '<comment>Rolling back:</comment> 2019_08_10_142230_update_test_index_mapping',
        '<info>Rolled back:</info> 2019_08_10_142230_update_test_index_mapping',
    ]);

    [$table, $migrator] = seededMigrator($output);

    DB::table($table)->insert([
        ['migration' => '2019_08_10_142230_update_test_index_mapping', 'batch' => 4],
    ]);

    expect($migrator->rollbackLastBatch())->toBe($migrator);
    expect(DB::table($table)->where([
        'migration' => '2019_08_10_142230_update_test_index_mapping',
        'batch' => 4,
    ])->exists())->toBeFalse();
});

it('does not roll back all migrations when some files are missing', function (): void {
    $output = outputExpectingLines([
        '<error>Migration is not found:</error> 2019_03_10_101500_create_test_index,2019_01_01_053550_drop_test_index',
    ]);

    [$table, $migrator] = seededMigrator($output);

    DB::table($table)->insert([
        ['migration' => '2019_03_10_101500_create_test_index', 'batch' => 2],
        ['migration' => '2019_01_01_053550_drop_test_index', 'batch' => 2],
    ]);

    expect($migrator->rollbackAll())->toBe($migrator);
});

it('rolls back all migrations when all files are present', function (): void {
    Index::shouldReceive('putMapping')->once();
    Index::shouldReceive('drop')->once();

    $output = outputExpectingLines([
        '<comment>Rolling back:</comment> 2019_08_10_142230_update_test_index_mapping',
        '<info>Rolled back:</info> 2019_08_10_142230_update_test_index_mapping',
        '<comment>Rolling back:</comment> 2018_12_01_081000_create_test_index',
        '<info>Rolled back:</info> 2018_12_01_081000_create_test_index',
    ]);

    [$table, $migrator] = seededMigrator($output);

    DB::table($table)->insert([
        ['migration' => '2019_08_10_142230_update_test_index_mapping', 'batch' => 2],
    ]);

    expect($migrator->rollbackAll())->toBe($migrator);
    expect(DB::table($table)->where('migration', '2019_08_10_142230_update_test_index_mapping')->exists())->toBeFalse();
    expect(DB::table($table)->where('migration', '2018_12_01_081000_create_test_index')->exists())->toBeFalse();
});

it('displays status', function (): void {
    $output = Mockery::mock(OutputStyle::class);

    $output->shouldReceive('table')->once()->with(
        ['Ran?', 'Last batch?', 'Migration'],
        [
            ['<info>Yes</info>', '<info>Yes</info>', '2018_12_01_081000_create_test_index'],
            ['<comment>No</comment>', '<comment>No</comment>', '2019_08_10_142230_update_test_index_mapping'],
        ]
    );

    [, $migrator] = seededMigrator($output);

    expect($migrator->showStatus())->toBe($migrator);
});

it('is ready when the repository and storage are ready', function (): void {
    [, $migrator] = seededMigrator(Mockery::mock(OutputStyle::class));

    expect($migrator->isReady())->toBeTrue();
});

it('is not ready when the repository is not ready', function (): void {
    $output = outputExpectingLines(['<error>Migration table is not yet created</error>']);
    [$table, $migrator] = seededMigrator($output);

    Schema::drop($table);

    expect($migrator->isReady())->toBeFalse();
});

it('is not ready when the storage is not ready', function (): void {
    config()->set('opensearch-migrations.storage_directory', '/non_existing_directory');

    $output = outputExpectingLines(['<error>Migration directory is not yet created</error>']);

    seededMigrator($output);

    $migrator = resolve(Migrator::class)->setOutput($output);

    expect($migrator->isReady())->toBeFalse();
});
