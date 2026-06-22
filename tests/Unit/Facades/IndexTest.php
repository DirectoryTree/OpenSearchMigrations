<?php

use DirectoryTree\OpenSearchMigrations\Facades\Index;
use DirectoryTree\OpenSearchMigrations\IndexManagerInterface;
use DirectoryTree\OpenSearchMigrations\Testing\Fakes\FakeIndexManager;

it('resolves the index manager interface', function (): void {
    expect(Index::getFacadeRoot())->toBeInstanceOf(IndexManagerInterface::class);
});

it('fakes the index manager', function (): void {
    config()->set('opensearch-migrations.index_name_prefix', 'tenant_');
    config()->set('opensearch-migrations.alias_name_prefix', 'tenant_');

    $fake = Index::fake();

    Index::create('posts');
    Index::putAlias('posts', 'published_posts');

    expect(Index::getFacadeRoot())->toBe($fake)
        ->and(app(IndexManagerInterface::class))->toBe($fake)
        ->and($fake)->toBeInstanceOf(FakeIndexManager::class);

    $fake
        ->assertCreated('posts')
        ->assertAliasPut('posts', 'published_posts');
});

it('fakes existing indices', function (): void {
    config()->set('opensearch-migrations.index_name_prefix', 'tenant_');

    $fake = Index::fake(existing: ['posts']);

    Index::dropIfExists('posts');

    $fake
        ->assertChecked('posts')
        ->assertDeleted('posts');
});
