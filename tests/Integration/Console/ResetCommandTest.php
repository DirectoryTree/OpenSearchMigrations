<?php

use DirectoryTree\OpenSearchMigrations\Console\ResetCommand;
use DirectoryTree\OpenSearchMigrations\Migrator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

it('does nothing if the migrator is not ready', function (): void {
    $migrator = Mockery::mock(Migrator::class);
    app()->instance(Migrator::class, $migrator);

    $migrator->shouldReceive('setOutput')->once()->andReturnSelf();

    $command = new ResetCommand;
    $command->setLaravel(app());

    $migrator->shouldReceive('isReady')->once()->andReturnFalse();
    $migrator->shouldNotReceive('rollbackAll');

    expect($command->run(new ArrayInput(['--force' => true]), new NullOutput))->toBe(1);
});

it('rolls back all migrations when the migrator is ready', function (): void {
    $migrator = Mockery::mock(Migrator::class);
    app()->instance(Migrator::class, $migrator);

    $migrator->shouldReceive('setOutput')->once()->andReturnSelf();

    $command = new ResetCommand;
    $command->setLaravel(app());

    $migrator->shouldReceive('isReady')->once()->andReturnTrue();
    $migrator->shouldReceive('rollbackAll')->once();

    expect($command->run(new ArrayInput(['--force' => true]), new NullOutput))->toBe(0);
});
