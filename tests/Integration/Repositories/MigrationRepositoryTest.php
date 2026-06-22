<?php

use DirectoryTree\OpenSearchMigrations\Repositories\MigrationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function seedMigrationRepositoryTable(): string
{
    $table = config('opensearch-migrations.table');

    DB::table($table)->insert([
        ['migration' => '2019_08_10_142230_update_test_index_mapping', 'batch' => 2],
        ['migration' => '2018_12_01_081000_create_test_index', 'batch' => 1],
    ]);

    return $table;
}

it('inserts records', function (): void {
    $table = seedMigrationRepositoryTable();
    $repository = app(MigrationRepository::class);

    $repository->insert('2019_12_12_201657_update_test_index_settings', 3);

    expect(DB::table($table)->where([
        'migration' => '2019_12_12_201657_update_test_index_settings',
        'batch' => 3,
    ])->exists())->toBeTrue();
});

it('matches Laravel migration table structure', function (): void {
    $table = seedMigrationRepositoryTable();
    $repository = app(MigrationRepository::class);

    expect(Schema::hasColumns($table, ['id', 'migration', 'batch']))->toBeTrue();

    $repository->insert('2019_12_12_201657_update_test_index_settings', 3);

    expect(DB::table($table)->max('id'))->toBe(3);
});

it('uses the configured database connection for the table migration', function (): void {
    seedMigrationRepositoryTable();

    config()->set('database.connections.opensearch_migrations', [
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);

    config()->set('opensearch-migrations.connection', 'opensearch_migrations');
    config()->set('opensearch-migrations.table', 'custom_opensearch_migrations');

    $migration = require dirname(__DIR__, 3).'/database/migrations/2019_15_12_112000_create_opensearch_migrations_table.php';

    $migration->up();

    expect(Schema::hasTable('custom_opensearch_migrations'))->toBeFalse();
    expect(Schema::connection('opensearch_migrations')->hasTable('custom_opensearch_migrations'))->toBeTrue();

    $migration->down();

    expect(Schema::connection('opensearch_migrations')->hasTable('custom_opensearch_migrations'))->toBeFalse();
});

it('checks whether records exist', function (): void {
    seedMigrationRepositoryTable();

    $repository = app(MigrationRepository::class);

    expect($repository->exists('2018_12_01_081000_create_test_index'))->toBeTrue();
    expect($repository->exists('2019_12_05_092345_drop_test_index'))->toBeFalse();
});

it('deletes records', function (): void {
    $table = seedMigrationRepositoryTable();
    $repository = app(MigrationRepository::class);

    $repository->delete('2019_12_01_081000_create_test_index');

    expect(DB::table($table)->where([
        'migration' => '2019_12_01_081000_create_test_index',
        'batch' => 1,
    ])->exists())->toBeFalse();
});

it('gets all records', function (): void {
    seedMigrationRepositoryTable();

    expect(app(MigrationRepository::class)->getAll()->toArray())->toBe([
        '2019_08_10_142230_update_test_index_mapping',
        '2018_12_01_081000_create_test_index',
    ]);
});

it('gets the last batch number', function (): void {
    $table = seedMigrationRepositoryTable();
    $repository = app(MigrationRepository::class);

    expect($repository->getLastBatchNumber())->toBe(2);

    DB::table($table)->delete();

    expect($repository->getLastBatchNumber())->toBeNull();
});

it('gets the last batch records', function (): void {
    seedMigrationRepositoryTable();

    expect(app(MigrationRepository::class)->getLastBatch()->toArray())->toBe([
        '2019_08_10_142230_update_test_index_mapping',
    ]);
});

it('is ready when the table exists', function (): void {
    seedMigrationRepositoryTable();

    expect(app(MigrationRepository::class)->isReady())->toBeTrue();
});

it('is not ready when the table does not exist', function (): void {
    $table = seedMigrationRepositoryTable();

    Schema::drop($table);

    expect(app(MigrationRepository::class)->isReady())->toBeFalse();
});

it('deletes all records', function (): void {
    seedMigrationRepositoryTable();
    $repository = app(MigrationRepository::class);

    expect($repository->getAll())->toHaveCount(2);

    $repository->deleteAll();

    expect($repository->getAll())->toHaveCount(0);
});
