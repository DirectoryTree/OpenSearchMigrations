<?php

use DirectoryTree\OpenSearchMigrations\Console\RefreshCommand;
use DirectoryTree\OpenSearchMigrations\Deployments\Deployment;
use DirectoryTree\OpenSearchMigrations\Migrator;
use DirectoryTree\OpenSearchMigrations\Repositories\DeploymentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

uses(RefreshDatabase::class);

it('resets and reruns all migrations', function (): void {
    $migrator = Mockery::mock(Migrator::class);
    app()->instance(Migrator::class, $migrator);

    $migrator->shouldReceive('setOutput')->once()->andReturnSelf();

    $command = new RefreshCommand;
    $command->setLaravel(app());

    $migrator->shouldReceive('prepare')->once()->andReturnSelf();
    $migrator->shouldReceive('rollbackAll')->once();
    $migrator->shouldReceive('migrateAll')->once();

    expect($command->run(new ArrayInput(['--force' => true]), new NullOutput))->toBe(0);
});

it('refuses to refresh while managed deployments exist', function (): void {
    $deployments = app(DeploymentRepository::class);
    $deployments->prepare();
    $deployments->save(Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: now(),
    )->cancelCandidate());

    $migrator = Mockery::mock(Migrator::class);
    $migrator->shouldReceive('setOutput')->once()->andReturnSelf();
    $migrator->shouldReceive('prepare')->once()->andReturnSelf();
    $migrator->shouldNotReceive('rollbackAll');
    $migrator->shouldNotReceive('migrateAll');

    app()->instance(Migrator::class, $migrator);

    $command = new RefreshCommand;
    $command->setLaravel(app());

    expect($command->run(new ArrayInput(['--force' => true]), new NullOutput))->toBe(1);
});
