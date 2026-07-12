<?php

use Carbon\CarbonImmutable;
use DirectoryTree\OpenSearchMigrations\Deployments\Deployment;
use DirectoryTree\OpenSearchMigrations\Deployments\DeploymentStatus;

it('returns unique deployment write indexes', function (): void {
    $deployment = Deployment::backfilling(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: CarbonImmutable::now(),
    )->stageCutover();

    expect($deployment->writeIndexes())->toBe([
        'posts_search',
        'posts_green',
        'posts_blue',
    ]);
});

it('expresses the deployment lifecycle through typed transitions', function (): void {
    $startedAt = CarbonImmutable::parse('2026-07-12 12:00:00');
    $readyAt = CarbonImmutable::parse('2026-07-12 13:00:00');
    $cutoverAt = CarbonImmutable::parse('2026-07-12 14:00:00');

    $deployment = Deployment::backfilling(
        name: 'posts',
        alias: 'posts_search',
        activeIndex: 'posts_blue',
        candidateIndex: 'posts_green',
        now: $startedAt,
    );

    expect($deployment->id)->toBeNull()
        ->and($deployment->status)->toBe(DeploymentStatus::Backfilling)
        ->and($deployment->createdAt)->toEqual($startedAt);

    $deployment = $deployment->markReady($readyAt)->stageCutover();

    expect($deployment->status)->toBe(DeploymentStatus::Ready)
        ->and($deployment->readyAt)->toEqual($readyAt)
        ->and($deployment->previousIndex)->toBe('posts_blue');

    $deployment = $deployment->completeCutover($cutoverAt);

    expect($deployment->status)->toBe(DeploymentStatus::Active)
        ->and($deployment->activeIndex)->toBe('posts_green')
        ->and($deployment->candidateIndex)->toBeNull()
        ->and($deployment->previousIndex)->toBe('posts_blue')
        ->and($deployment->cutoverAt)->toEqual($cutoverAt);

    $deployment = $deployment->stageRollback()->completeRollback($cutoverAt);

    expect($deployment->activeIndex)->toBe('posts_blue')
        ->and($deployment->previousIndex)->toBe('posts_green');
});
