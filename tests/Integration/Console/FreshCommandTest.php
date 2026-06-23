<?php

use DirectoryTree\OpenSearchMigrations\Console\FreshCommand;
use DirectoryTree\OpenSearchMigrations\IndexManagerInterface;
use DirectoryTree\OpenSearchMigrations\Migrator;
use DirectoryTree\OpenSearchMigrations\Repositories\MigrationRepository;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

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

    $migrator->shouldReceive('prepare')->once()->andReturnSelf();
    $index->shouldReceive('drop')->once()->with('*');
    $repository->shouldReceive('deleteAll')->once();
    $migrator->shouldReceive('migrateAll')->once();

    expect($command->run(new ArrayInput(['--force' => true]), new NullOutput))->toBe(0);
});
