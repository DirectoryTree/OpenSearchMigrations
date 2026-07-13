<?php

use DirectoryTree\OpenSearchMigrations\Console\FreshCommand;
use DirectoryTree\OpenSearchMigrations\Deployments\Deployment;
use DirectoryTree\OpenSearchMigrations\IndexManagerInterface;
use DirectoryTree\OpenSearchMigrations\Migrator;
use DirectoryTree\OpenSearchMigrations\Repositories\DeploymentRepository;
use DirectoryTree\OpenSearchMigrations\Repositories\MigrationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

uses(RefreshDatabase::class);

it('drops indices and migrations', function (): void {
    $migrator = Mockery::mock(Migrator::class);
    $repository = Mockery::mock(MigrationRepository::class);
    $index = Mockery::mock(IndexManagerInterface::class);
    $deployments = app(DeploymentRepository::class);
    $deployments->prepare();
    $deployments->save(Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: now(),
    )->cancelCandidate());

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

    expect($command->run(new ArrayInput(['--force' => true]), new NullOutput))->toBe(0)
        ->and($deployments->exists())->toBeFalse();
});
