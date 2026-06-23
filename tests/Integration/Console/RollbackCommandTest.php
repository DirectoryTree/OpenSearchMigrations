<?php

use DirectoryTree\OpenSearchMigrations\Console\RollbackCommand;
use DirectoryTree\OpenSearchMigrations\Migrator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

it('rolls back one migration when a file name is provided', function (): void {
    $migrator = Mockery::mock(Migrator::class);
    app()->instance(Migrator::class, $migrator);

    $migrator->shouldReceive('setOutput')->once()->andReturnSelf();

    $command = new RollbackCommand;
    $command->setLaravel(app());

    $migrator->shouldReceive('prepare')->once()->andReturnSelf();
    $migrator->shouldReceive('rollbackOne')->once()->with('test_file_name');

    expect($command->run(new ArrayInput(['--force' => true, 'fileName' => 'test_file_name']), new NullOutput))->toBe(0);
});

it('rolls back the last batch when a file name is not provided', function (): void {
    $migrator = Mockery::mock(Migrator::class);
    app()->instance(Migrator::class, $migrator);

    $migrator->shouldReceive('setOutput')->once()->andReturnSelf();

    $command = new RollbackCommand;
    $command->setLaravel(app());

    $migrator->shouldReceive('prepare')->once()->andReturnSelf();
    $migrator->shouldReceive('rollbackLastBatch')->once();

    expect($command->run(new ArrayInput(['--force' => true]), new NullOutput))->toBe(0);
});
