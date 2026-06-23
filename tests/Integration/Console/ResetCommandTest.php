<?php

use DirectoryTree\OpenSearchMigrations\Console\ResetCommand;
use DirectoryTree\OpenSearchMigrations\Migrator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

it('rolls back all migrations', function (): void {
    $migrator = Mockery::mock(Migrator::class);
    app()->instance(Migrator::class, $migrator);

    $migrator->shouldReceive('setOutput')->once()->andReturnSelf();

    $command = new ResetCommand;
    $command->setLaravel(app());

    $migrator->shouldReceive('prepare')->once()->andReturnSelf();
    $migrator->shouldReceive('rollbackAll')->once();

    expect($command->run(new ArrayInput(['--force' => true]), new NullOutput))->toBe(0);
});
