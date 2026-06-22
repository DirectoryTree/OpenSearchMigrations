<?php

use DirectoryTree\OpenSearchMigrations\Console\MigrateCommand;
use DirectoryTree\OpenSearchMigrations\Migrator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

it('does nothing if the migrator is not ready', function (): void {
    $migrator = Mockery::mock(Migrator::class);
    app()->instance(Migrator::class, $migrator);

    $migrator->shouldReceive('setOutput')->once()->andReturnSelf();

    $command = new MigrateCommand;
    $command->setLaravel(app());

    $migrator->shouldReceive('isReady')->once()->andReturnFalse();
    $migrator->shouldNotReceive('migrateOne');
    $migrator->shouldNotReceive('migrateAll');

    expect($command->run(new ArrayInput(['--force' => true]), new NullOutput))->toBe(1);
});

it('runs one migration when a file name is provided', function (): void {
    $migrator = Mockery::mock(Migrator::class);
    app()->instance(Migrator::class, $migrator);

    $migrator->shouldReceive('setOutput')->once()->andReturnSelf();

    $command = new MigrateCommand;
    $command->setLaravel(app());

    $migrator->shouldReceive('isReady')->once()->andReturnTrue();
    $migrator->shouldReceive('migrateOne')->once()->with('test_file_name');

    expect($command->run(new ArrayInput(['--force' => true, 'fileName' => 'test_file_name']), new NullOutput))->toBe(0);
});

it('runs all migrations when a file name is not provided', function (): void {
    $migrator = Mockery::mock(Migrator::class);
    app()->instance(Migrator::class, $migrator);

    $migrator->shouldReceive('setOutput')->once()->andReturnSelf();

    $command = new MigrateCommand;
    $command->setLaravel(app());

    $migrator->shouldReceive('isReady')->once()->andReturnTrue();
    $migrator->shouldReceive('migrateAll')->once();

    expect($command->run(new ArrayInput(['--force' => true]), new NullOutput))->toBe(0);
});
