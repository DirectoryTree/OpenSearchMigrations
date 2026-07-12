<?php

use DirectoryTree\OpenSearchMigrations\Console\ResetCommand;
use DirectoryTree\OpenSearchMigrations\Deployments\Deployment;
use DirectoryTree\OpenSearchMigrations\Migrator;
use DirectoryTree\OpenSearchMigrations\Repositories\DeploymentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

uses(RefreshDatabase::class);

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

it('refuses to reset while managed deployments exist', function (): void {
    $deployments = app(DeploymentRepository::class);
    $deployments->prepare();
    $deployments->save(Deployment::backfilling(
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

    app()->instance(Migrator::class, $migrator);

    $command = new ResetCommand;
    $command->setLaravel(app());

    expect($command->run(new ArrayInput(['--force' => true]), new NullOutput))->toBe(1);
});
