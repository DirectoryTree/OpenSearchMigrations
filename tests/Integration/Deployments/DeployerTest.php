<?php

use DirectoryTree\OpenSearchAdapter\Indices\IndexBlueprint;
use DirectoryTree\OpenSearchAdapter\Indices\IndexManagerInterface as AdapterIndexManagerInterface;
use DirectoryTree\OpenSearchAdapter\Indices\Mapping;
use DirectoryTree\OpenSearchAdapter\Indices\Settings;
use DirectoryTree\OpenSearchAdapter\Testing\Fakes\FakeIndexManager;
use DirectoryTree\OpenSearchClient\OpenSearchManager;
use DirectoryTree\OpenSearchMigrations\Adapters\IndexManagerAdapter;
use DirectoryTree\OpenSearchMigrations\Deployer;
use DirectoryTree\OpenSearchMigrations\Deployments\Deployment;
use DirectoryTree\OpenSearchMigrations\Deployments\DeploymentException;
use DirectoryTree\OpenSearchMigrations\Deployments\DeploymentStatus;
use DirectoryTree\OpenSearchMigrations\Repositories\DeploymentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use OpenSearch\Client;
use OpenSearch\Namespaces\IndicesNamespace;

uses(RefreshDatabase::class);

it('provisions a versioned candidate before enabling concurrent writes', function (): void {
    Date::setTestNow('2026-07-12 12:34:56.789');
    config()->set('opensearch-migrations.index_name_prefix', 'test_');
    config()->set('opensearch-migrations.alias_name_prefix', 'test_');

    $repository = app(DeploymentRepository::class);
    $adapterIndexes = new FakeIndexManager;
    $indexes = new IndexManagerAdapter($adapterIndexes);

    $indices = mock(IndicesNamespace::class);
    $indices->shouldReceive('getAlias')->once()->with([
        'name' => 'test_posts_search',
    ])->andReturn([
        'test_posts_blue' => ['aliases' => ['test_posts_search' => []]],
    ]);

    $client = mock(Client::class);
    $client->shouldReceive('indices')->once()->andReturn($indices);

    $openSearch = mock(OpenSearchManager::class);
    $openSearch->shouldReceive('default')->once()->andReturn($client);

    $manager = new Deployer($repository, $indexes, $adapterIndexes, $openSearch);

    $deployment = $manager->provision('posts', 'posts_search', function (Mapping $mapping, Settings $settings): void {
        $mapping->text('title');
        $settings->index(['number_of_replicas' => 0]);
    });

    $adapterIndexes->assertCreated(new IndexBlueprint(
        'test_posts_v20260712123456789',
        (new Mapping)->text('title'),
        (new Settings)->index(['number_of_replicas' => 0]),
    ));

    expect($deployment->name)->toBe('posts')
        ->and($deployment->alias)->toBe('test_posts_search')
        ->and($deployment->activeIndex)->toBe('test_posts_blue')
        ->and($deployment->candidateIndex)->toBe('test_posts_v20260712123456789')
        ->and($deployment->status)->toBe(DeploymentStatus::Provisioned)
        ->and($deployment->writeIndexes())->toBe(['test_posts_search']);

    $deployment = $manager->beginBackfill('posts');

    expect($deployment->status)->toBe(DeploymentStatus::Backfilling)
        ->and($deployment->writeIndexes())->toBe([
            'test_posts_search',
            'test_posts_v20260712123456789',
        ]);
});

it('marks a candidate ready and cuts over its alias atomically', function (): void {
    $repository = app(DeploymentRepository::class);
    $repository->prepare();
    $repository->save(Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: Date::now(),
    ));

    $adapterIndexes = new FakeIndexManager;
    $indexes = new IndexManagerAdapter($adapterIndexes);

    $indices = mock(IndicesNamespace::class);
    $indices->shouldReceive('getAlias')->once()->with([
        'name' => 'posts_search',
    ])->andReturn([
        'posts_blue' => ['aliases' => ['posts_search' => []]],
    ]);
    $indices->shouldReceive('updateAliases')->once()->with([
        'body' => [
            'actions' => [
                ['remove' => ['index' => 'posts_blue', 'alias' => 'posts_search']],
                ['add' => ['index' => 'posts_green', 'alias' => 'posts_search', 'is_write_index' => true]],
            ],
        ],
    ]);

    $client = mock(Client::class);
    $client->shouldReceive('indices')->twice()->andReturn($indices);

    $openSearch = mock(OpenSearchManager::class);
    $openSearch->shouldReceive('default')->twice()->andReturn($client);

    $manager = new Deployer($repository, $indexes, $adapterIndexes, $openSearch);

    expect($manager->beginBackfill('posts')->status)->toBe(DeploymentStatus::Backfilling);
    expect($manager->markReady('posts')->status)->toBe(DeploymentStatus::Ready);

    $deployment = $manager->cutover('posts');

    expect($deployment->activeIndex)->toBe('posts_green')
        ->and($deployment->candidateIndex)->toBeNull()
        ->and($deployment->previousIndex)->toBe('posts_blue')
        ->and($deployment->status)->toBe(DeploymentStatus::Active);
});

it('does not mark a provisioned candidate ready before backfilling begins', function (): void {
    $repository = app(DeploymentRepository::class);
    $repository->prepare();
    $repository->save(Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: Date::now(),
    ));

    $adapterIndexes = new FakeIndexManager;
    $indexes = new IndexManagerAdapter($adapterIndexes);
    $openSearch = mock(OpenSearchManager::class);

    $deployer = new Deployer($repository, $indexes, $adapterIndexes, $openSearch);

    expect(fn () => $deployer->markReady('posts'))
        ->toThrow(DeploymentException::class, 'The candidate index must be backfilling before it can be marked as ready.');
});

it('finishes a cutover when the alias was already moved', function (): void {
    $repository = app(DeploymentRepository::class);
    $repository->prepare();
    $repository->save(Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: Date::now(),
    )->beginBackfill()->markReady(Date::now())->stageCutover());

    $adapterIndexes = new FakeIndexManager;
    $indexes = new IndexManagerAdapter($adapterIndexes);

    $indices = mock(IndicesNamespace::class);
    $indices->shouldReceive('getAlias')->once()->andReturn([
        'posts_green' => ['aliases' => ['posts_search' => []]],
    ]);
    $indices->shouldNotReceive('updateAliases');

    $client = mock(Client::class);
    $client->shouldReceive('indices')->once()->andReturn($indices);

    $openSearch = mock(OpenSearchManager::class);
    $openSearch->shouldReceive('default')->once()->andReturn($client);

    $deployment = (new Deployer($repository, $indexes, $adapterIndexes, $openSearch))->cutover('posts');

    expect($deployment->activeIndex)->toBe('posts_green')
        ->and($deployment->candidateIndex)->toBeNull()
        ->and($deployment->previousIndex)->toBe('posts_blue');
});

it('rolls a deployment back atomically', function (): void {
    $repository = app(DeploymentRepository::class);
    $repository->prepare();
    $repository->save(Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: Date::now(),
    )->beginBackfill()->markReady(Date::now())->stageCutover()->completeCutover(Date::now()));

    $adapterIndexes = new FakeIndexManager;
    $indexes = new IndexManagerAdapter($adapterIndexes);

    $indices = mock(IndicesNamespace::class);
    $indices->shouldReceive('getAlias')->once()->andReturn([
        'posts_green' => ['aliases' => ['posts_search' => []]],
    ]);
    $indices->shouldReceive('updateAliases')->once();

    $client = mock(Client::class);
    $client->shouldReceive('indices')->twice()->andReturn($indices);

    $openSearch = mock(OpenSearchManager::class);
    $openSearch->shouldReceive('default')->twice()->andReturn($client);

    $deployment = (new Deployer($repository, $indexes, $adapterIndexes, $openSearch))->rollback('posts');

    expect($deployment->activeIndex)->toBe('posts_blue')
        ->and($deployment->candidateIndex)->toBeNull()
        ->and($deployment->previousIndex)->toBe('posts_green');
});

it('cancels and deletes a candidate index', function (): void {
    $repository = app(DeploymentRepository::class);
    $repository->prepare();
    $repository->save(Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: Date::now(),
    ));

    $adapterIndexes = new FakeIndexManager;
    $indexes = new IndexManagerAdapter($adapterIndexes);

    $indices = mock(IndicesNamespace::class);
    $indices->shouldReceive('getAlias')->once()->andReturn([
        'posts_blue' => ['aliases' => ['posts_search' => []]],
    ]);

    $client = mock(Client::class);
    $client->shouldReceive('indices')->once()->andReturn($indices);

    $openSearch = mock(OpenSearchManager::class);
    $openSearch->shouldReceive('default')->once()->andReturn($client);

    $deployment = (new Deployer($repository, $indexes, $adapterIndexes, $openSearch))->cancel('posts');

    $adapterIndexes->assertDeleted('posts_green');

    expect($deployment->activeIndex)->toBe('posts_blue')
        ->and($deployment->candidateIndex)->toBeNull()
        ->and($deployment->status)->toBe(DeploymentStatus::Active);
});

it('retires the previous physical index', function (): void {
    $repository = app(DeploymentRepository::class);
    $repository->prepare();
    $repository->save(Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: Date::now(),
    )->beginBackfill()->markReady(Date::now())->stageCutover()->completeCutover(Date::now()));

    $adapterIndexes = new FakeIndexManager;
    $indexes = new IndexManagerAdapter($adapterIndexes);
    $openSearch = mock(OpenSearchManager::class);

    $deployment = (new Deployer($repository, $indexes, $adapterIndexes, $openSearch))->retire('posts');

    $adapterIndexes->assertDeleted('posts_blue');

    expect($deployment->activeIndex)->toBe('posts_green')
        ->and($deployment->previousIndex)->toBeNull();
});

it('restores deployment state when retirement fails', function (): void {
    $repository = app(DeploymentRepository::class);
    $repository->prepare();
    $repository->save(Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: Date::now(),
    )->beginBackfill()->markReady(Date::now())->stageCutover()->completeCutover(Date::now()));

    $adapterIndexes = mock(AdapterIndexManagerInterface::class);
    $adapterIndexes->shouldReceive('delete')->once()->andThrow(new DeploymentException('Delete failed.'));

    $indexes = new IndexManagerAdapter($adapterIndexes);
    $openSearch = mock(OpenSearchManager::class);

    expect(fn () => (new Deployer($repository, $indexes, $adapterIndexes, $openSearch))->retire('posts'))
        ->toThrow(DeploymentException::class, 'Delete failed.');

    expect($repository->findOrFail('posts')->previousIndex)->toBe('posts_blue');
});
