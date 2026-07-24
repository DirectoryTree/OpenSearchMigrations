<?php

use DirectoryTree\OpenSearchAdapter\Indices\Mapping;
use DirectoryTree\OpenSearchAdapter\Indices\Settings;
use DirectoryTree\OpenSearchMigrations\Facades\Index;
use DirectoryTree\OpenSearchMigrations\IndexManagerInterface;
use DirectoryTree\OpenSearchMigrations\Testing\Fakes\FakeIndexManager;
use PHPUnit\Framework\ExpectationFailedException;

it('resolves the index manager interface', function (): void {
    expect(Index::getFacadeRoot())->toBeInstanceOf(IndexManagerInterface::class);
});

it('fakes the index manager', function (): void {
    config()->set('opensearch-migrations.index_name_prefix', 'tenant_');
    config()->set('opensearch-migrations.alias_name_prefix', 'tenant_');

    $fake = Index::fake();

    Index::create('posts', function (Mapping $mapping, Settings $settings): void {
        $mapping->text('title');
        $settings->index(['number_of_replicas' => 0]);
    });
    Index::putAlias('posts', 'published_posts');

    expect(Index::getFacadeRoot())->toBe($fake)
        ->and(app(IndexManagerInterface::class))->toBe($fake)
        ->and($fake)->toBeInstanceOf(FakeIndexManager::class);

    $fake
        ->assertCreated('posts')
        ->assertAliasPut('posts', 'published_posts');
});

it('asserts created index definitions with a callback', function (): void {
    config()->set('opensearch-migrations.index_name_prefix', 'tenant_');

    $fake = Index::fake();

    Index::create('posts', function (Mapping $mapping, Settings $settings): void {
        $mapping->text('title');
        $settings->index(['number_of_replicas' => 0]);
    });

    $fake->assertCreated(
        'posts',
        fn (?Mapping $mapping, ?Settings $settings): bool => (
            $mapping?->toArray() === [
                'properties' => [
                    'title' => ['type' => 'text'],
                ],
            ]
            && $settings?->toArray() === [
                'index' => ['number_of_replicas' => 0],
            ]
        )
    );
});

it('fails when a created index does not satisfy the callback', function (): void {
    $fake = Index::fake();

    Index::create('posts');

    expect(fn () => $fake->assertCreated('posts', fn (): bool => false))
        ->toThrow(ExpectationFailedException::class);
});

it('asserts updated mappings with an optional callback', function (): void {
    config()->set('opensearch-migrations.index_name_prefix', 'tenant_');

    $fake = Index::fake();

    Index::putMapping('posts', function (Mapping $mapping): void {
        $mapping->keyword('status');
    });

    $fake
        ->assertMappingPut('posts')
        ->assertMappingPut('posts', fn (Mapping $mapping): bool => $mapping->toArray() === [
            'properties' => [
                'status' => ['type' => 'keyword'],
            ],
        ]);
});

it('fails when an updated mapping does not satisfy the callback', function (): void {
    $fake = Index::fake();

    Index::putMapping('posts', function (Mapping $mapping): void {
        $mapping->keyword('status');
    });

    expect(fn () => $fake->assertMappingPut('posts', fn (): bool => false))
        ->toThrow(ExpectationFailedException::class);
});

it('asserts updated settings with an optional callback', function (): void {
    config()->set('opensearch-migrations.index_name_prefix', 'tenant_');

    $fake = Index::fake();

    Index::putSettings('posts', function (Settings $settings): void {
        $settings->index(['refresh_interval' => -1]);
    });

    $fake
        ->assertSettingsPut('posts')
        ->assertSettingsPut('posts', fn (Settings $settings): bool => $settings->toArray() === [
            'index' => ['refresh_interval' => -1],
        ]);
});

it('fails when updated settings do not satisfy the callback', function (): void {
    $fake = Index::fake();

    Index::putSettings('posts', function (Settings $settings): void {
        $settings->index(['refresh_interval' => -1]);
    });

    expect(fn () => $fake->assertSettingsPut('posts', fn (): bool => false))
        ->toThrow(ExpectationFailedException::class);
});

it('fakes existing indices', function (): void {
    config()->set('opensearch-migrations.index_name_prefix', 'tenant_');

    $fake = Index::fake(existing: ['posts']);

    Index::dropIfExists('posts');

    $fake
        ->assertChecked('posts')
        ->assertDeleted('posts');
});
