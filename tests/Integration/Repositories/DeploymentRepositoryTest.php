<?php

use DirectoryTree\OpenSearchMigrations\Deployments\Deployment;
use DirectoryTree\OpenSearchMigrations\Deployments\DeploymentStatus;
use DirectoryTree\OpenSearchMigrations\Repositories\DeploymentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('prepares the deployment repository table', function (): void {
    $table = config('opensearch-deployments.table');

    expect(Schema::hasTable($table))->toBeFalse();

    app(DeploymentRepository::class)->prepare();

    expect(Schema::hasTable($table))->toBeTrue()
        ->and(Schema::hasColumns($table, [
            'id',
            'name',
            'alias',
            'active_index',
            'candidate_index',
            'previous_index',
            'status',
            'ready_at',
            'cutover_at',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('stores and retrieves deployment state', function (): void {
    Date::setTestNow('2026-07-12 12:00:00');

    $repository = app(DeploymentRepository::class);
    $repository->prepare();

    $deployment = $repository->save(Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: Date::now(),
    ));

    expect($deployment->name)->toBe('posts')
        ->and($deployment->alias)->toBe('posts_search')
        ->and($deployment->activeIndex)->toBe('posts_blue')
        ->and($deployment->candidateIndex)->toBe('posts_green')
        ->and($deployment->previousIndex)->toBeNull()
        ->and($deployment->status)->toBe(DeploymentStatus::Provisioned)
        ->and($deployment->createdAt->toDateTimeString())->toBe('2026-07-12 12:00:00');
});

it('updates deployment state without replacing its creation time', function (): void {
    Date::setTestNow('2026-07-12 12:00:00');

    $repository = app(DeploymentRepository::class);
    $repository->prepare();

    $repository->save(Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: Date::now(),
    ));

    Date::setTestNow('2026-07-12 13:00:00');

    $deployment = $repository->save(
        $repository->findOrFail('posts')->beginBackfill()->markReady(Date::now())
    );

    expect($deployment->status)->toBe(DeploymentStatus::Ready)
        ->and($deployment->createdAt->toDateTimeString())->toBe('2026-07-12 12:00:00')
        ->and($deployment->updatedAt->toDateTimeString())->toBe('2026-07-12 13:00:00');
});

it('uses the configured deployment connection', function (): void {
    config()->set('database.connections.opensearch_deployments', [
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);
    config()->set('opensearch-deployments.connection', 'opensearch_deployments');
    config()->set('opensearch-deployments.table', 'custom_opensearch_deployments');

    app(DeploymentRepository::class)->prepare();

    expect(Schema::hasTable('custom_opensearch_deployments'))->toBeFalse()
        ->and(Schema::connection('opensearch_deployments')->hasTable('custom_opensearch_deployments'))->toBeTrue();
});

it('checks for and deletes all deployment records', function (): void {
    $repository = app(DeploymentRepository::class);
    $repository->prepare();

    expect($repository->exists())->toBeFalse();

    $repository->save(Deployment::provisioned(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: Date::now(),
    )->cancelCandidate());

    expect($repository->exists())->toBeTrue();

    $repository->deleteAll();

    expect($repository->exists())->toBeFalse();
});
