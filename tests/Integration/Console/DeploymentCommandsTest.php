<?php

use Carbon\CarbonImmutable;
use DirectoryTree\OpenSearchMigrations\Console\DeploymentBackfillCommand;
use DirectoryTree\OpenSearchMigrations\Console\DeploymentCancelCommand;
use DirectoryTree\OpenSearchMigrations\Console\DeploymentCutoverCommand;
use DirectoryTree\OpenSearchMigrations\Console\DeploymentReadyCommand;
use DirectoryTree\OpenSearchMigrations\Console\DeploymentRetireCommand;
use DirectoryTree\OpenSearchMigrations\Console\DeploymentRollbackCommand;
use DirectoryTree\OpenSearchMigrations\Console\DeploymentStatusCommand;
use DirectoryTree\OpenSearchMigrations\Deployer;
use DirectoryTree\OpenSearchMigrations\Deployments\Deployment;
use DirectoryTree\OpenSearchMigrations\Repositories\DeploymentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

uses(RefreshDatabase::class);

it('registers the deployment commands', function (): void {
    expect(array_keys(Artisan::all()))->toContain(
        'opensearch:deploy:backfill',
        'opensearch:deploy:cancel',
        'opensearch:deploy:cutover',
        'opensearch:deploy:ready',
        'opensearch:deploy:retire',
        'opensearch:deploy:rollback',
        'opensearch:deploy:status',
    );
});

it('begins backfilling a deployment', function (): void {
    $deployment = Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: CarbonImmutable::now(),
    )->beginBackfill();

    $deployer = Mockery::mock(Deployer::class);
    $deployer->shouldReceive('backfill')->once()->with('posts')->andReturn($deployment);
    app()->instance(Deployer::class, $deployer);

    $command = new DeploymentBackfillCommand;
    $command->setLaravel(app());

    expect($command->run(new ArrayInput([
        'name' => 'posts',
        '--force' => true,
    ]), new NullOutput))->toBe(0);
});

it('marks a deployment ready', function (): void {
    $deployment = Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: CarbonImmutable::now(),
    )->beginBackfill()->markReady(CarbonImmutable::now());

    $deployer = Mockery::mock(Deployer::class);
    $deployer->shouldReceive('markReady')->once()->with('posts')->andReturn($deployment);
    app()->instance(Deployer::class, $deployer);

    $command = new DeploymentReadyCommand;
    $command->setLaravel(app());

    expect($command->run(new ArrayInput([
        'name' => 'posts',
        '--force' => true,
    ]), new NullOutput))->toBe(0);
});

it('cuts over a deployment', function (): void {
    $deployment = Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: CarbonImmutable::now(),
    )->beginBackfill()->markReady(CarbonImmutable::now());

    $deployer = Mockery::mock(Deployer::class);
    $deployer->shouldReceive('cutover')->once()->with('posts')->andReturn($deployment);
    app()->instance(Deployer::class, $deployer);

    $command = new DeploymentCutoverCommand;
    $command->setLaravel(app());

    expect($command->run(new ArrayInput([
        'name' => 'posts',
        '--force' => true,
    ]), new NullOutput))->toBe(0);
});

it('rolls back a deployment', function (): void {
    $deployment = Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: CarbonImmutable::now(),
    )->beginBackfill()->markReady(CarbonImmutable::now());

    $deployer = Mockery::mock(Deployer::class);
    $deployer->shouldReceive('rollback')->once()->with('posts')->andReturn($deployment);
    app()->instance(Deployer::class, $deployer);

    $command = new DeploymentRollbackCommand;
    $command->setLaravel(app());

    expect($command->run(new ArrayInput([
        'name' => 'posts',
        '--force' => true,
    ]), new NullOutput))->toBe(0);
});

it('cancels a deployment', function (): void {
    $deployment = Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: CarbonImmutable::now(),
    );

    $deployer = Mockery::mock(Deployer::class);
    $deployer->shouldReceive('cancel')->once()->with('posts')->andReturn($deployment);
    app()->instance(Deployer::class, $deployer);

    $command = new DeploymentCancelCommand;
    $command->setLaravel(app());

    expect($command->run(new ArrayInput([
        'name' => 'posts',
        '--force' => true,
    ]), new NullOutput))->toBe(0);
});

it('retires a deployment', function (): void {
    $deployment = Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: CarbonImmutable::now(),
    );

    $deployer = Mockery::mock(Deployer::class);
    $deployer->shouldReceive('retire')->once()->with('posts')->andReturn($deployment);
    app()->instance(Deployer::class, $deployer);

    $command = new DeploymentRetireCommand;
    $command->setLaravel(app());

    expect($command->run(new ArrayInput([
        'name' => 'posts',
        '--force' => true,
    ]), new NullOutput))->toBe(0);
});

it('shows deployment status', function (): void {
    $deployments = app(DeploymentRepository::class);
    $deployments->prepare();
    $deployments->save(Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: CarbonImmutable::now(),
    ));

    $command = new DeploymentStatusCommand;
    $command->setLaravel(app());

    $output = new BufferedOutput;

    expect($command->run(new ArrayInput([]), $output))->toBe(0)
        ->and($output->fetch())->toContain(
            'posts',
            'provisioned',
            'posts_search',
            'posts_blue',
            'posts_green',
        );
});
