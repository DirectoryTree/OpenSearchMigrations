<?php

use DirectoryTree\OpenSearchMigrations\Console\StatusCommand;
use DirectoryTree\OpenSearchMigrations\Migrator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

it('shows migration status', function (): void {
    $migrator = Mockery::mock(Migrator::class);
    app()->instance(Migrator::class, $migrator);

    $migrator->shouldReceive('setOutput')->once()->andReturnSelf();

    $command = new StatusCommand;
    $command->setLaravel(app());

    $migrator->shouldReceive('prepare')->once()->andReturnSelf();
    $migrator->shouldReceive('showStatus')->once();

    expect($command->run(new ArrayInput([]), new NullOutput))->toBe(0);
});
