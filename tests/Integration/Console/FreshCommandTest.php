<?php

use DirectoryTree\OpenSearchMigrations\Console\FreshCommand;
use DirectoryTree\OpenSearchMigrations\IndexManagerInterface;
use DirectoryTree\OpenSearchMigrations\Migrator;
use DirectoryTree\OpenSearchMigrations\Repositories\MigrationRepository;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

it('does nothing if the migrator is not ready', function (): void {
    $migrator = Mockery::mock(Migrator::class);
    $repository = Mockery::mock(MigrationRepository::class);
    $index = Mockery::mock(IndexManagerInterface::class);

    app()->instance(Migrator::class, $migrator);

    $migrator->shouldReceive('setOutput')->once()->andReturnSelf();
    app()->instance(MigrationRepository::class, $repository);
    app()->instance(IndexManagerInterface::class, $index);

    $command = new FreshCommand;
    $command->setLaravel(app());

    $migrator->shouldReceive('isReady')->once()->andReturnFalse();
    $index->shouldNotReceive('drop');
    $repository->shouldNotReceive('deleteAll');
    $migrator->shouldNotReceive('migrateAll');

    expect($command->run(new ArrayInput(['--force' => true]), new NullOutput))->toBe(1);
});

it('drops indices and migrations', function (): void {
    $migrator = Mockery::mock(Migrator::class);
    $repository = Mockery::mock(MigrationRepository::class);
    $index = Mockery::mock(IndexManagerInterface::class);

    app()->instance(Migrator::class, $migrator);

    $migrator->shouldReceive('setOutput')->once()->andReturnSelf();
    app()->instance(MigrationRepository::class, $repository);
    app()->instance(IndexManagerInterface::class, $index);

    $command = new FreshCommand;
    $command->setLaravel(app());

    $migrator->shouldReceive('isReady')->once()->andReturnTrue();
    $index->shouldReceive('drop')->once()->with('*');
    $repository->shouldReceive('deleteAll')->once();
    $migrator->shouldReceive('migrateAll')->once();

    expect($command->run(new ArrayInput(['--force' => true]), new NullOutput))->toBe(0);
});
