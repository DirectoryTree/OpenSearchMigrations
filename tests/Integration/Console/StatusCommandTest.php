<?php

use DirectoryTree\OpenSearchMigrations\Console\StatusCommand;
use DirectoryTree\OpenSearchMigrations\Migrator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

it('does nothing if the migrator is not ready', function (): void {
    $migrator = Mockery::mock(Migrator::class);
    app()->instance(Migrator::class, $migrator);

    $migrator->shouldReceive('setOutput')->once()->andReturnSelf();

    $command = new StatusCommand;
    $command->setLaravel(app());

    $migrator->shouldReceive('isReady')->once()->andReturnFalse();
    $migrator->shouldNotReceive('showStatus');

    expect($command->run(new ArrayInput([]), new NullOutput))->toBe(1);
});

it('shows status when the migrator is ready', function (): void {
    $migrator = Mockery::mock(Migrator::class);
    app()->instance(Migrator::class, $migrator);

    $migrator->shouldReceive('setOutput')->once()->andReturnSelf();

    $command = new StatusCommand;
    $command->setLaravel(app());

    $migrator->shouldReceive('isReady')->once()->andReturnTrue();
    $migrator->shouldReceive('showStatus')->once();

    expect($command->run(new ArrayInput([]), new NullOutput))->toBe(0);
});
