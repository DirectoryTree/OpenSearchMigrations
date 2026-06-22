<?php

use DirectoryTree\OpenSearchAdapter\Indices\Alias;
use DirectoryTree\OpenSearchAdapter\Indices\IndexBlueprint;
use DirectoryTree\OpenSearchAdapter\Indices\Mapping;
use DirectoryTree\OpenSearchAdapter\Indices\Settings;
use DirectoryTree\OpenSearchAdapter\Testing\Fakes\FakeIndexManager;
use DirectoryTree\OpenSearchMigrations\Adapters\IndexManagerAdapter;

it('creates indexes without modifiers', function (string $prefix): void {
    config()->set('opensearch-migrations.index_name_prefix', $prefix);

    $indexManager = new FakeIndexManager;
    $adapter = new IndexManagerAdapter($indexManager);

    $adapter->create('test');

    $indexManager->assertCreated(new IndexBlueprint($prefix.'test'));
})->with('prefixes');

it('creates indexes with modifiers', function (string $prefix): void {
    config()->set('opensearch-migrations.index_name_prefix', $prefix);

    $indexManager = new FakeIndexManager;
    $adapter = new IndexManagerAdapter($indexManager);

    $adapter->create('test', function (Mapping $mapping, Settings $settings): void {
        $mapping->text('title');
        $settings->index(['number_of_replicas' => 2]);
    });

    $indexManager->assertCreated(new IndexBlueprint(
        $prefix.'test',
        (new Mapping)->text('title'),
        (new Settings)->index(['number_of_replicas' => 2])
    ));
})->with('prefixes');

it('creates indexes only when they do not exist', function (string $prefix): void {
    config()->set('opensearch-migrations.index_name_prefix', $prefix);

    $indexManager = new FakeIndexManager;
    $adapter = new IndexManagerAdapter($indexManager);

    $adapter->createIfNotExists('test');

    $indexManager
        ->assertChecked($prefix.'test')
        ->assertCreated(new IndexBlueprint($prefix.'test'));
})->with('prefixes');

it('updates mappings with modifiers', function (string $prefix): void {
    config()->set('opensearch-migrations.index_name_prefix', $prefix);

    $indexManager = new FakeIndexManager;
    $adapter = new IndexManagerAdapter($indexManager);

    $adapter->putMapping('test', function (Mapping $mapping): void {
        $mapping->disableSource()->text('title');
    });

    $indexManager->assertMappingPut(
        $prefix.'test',
        (new Mapping)->disableSource()->text('title')
    );
})->with('prefixes');

it('updates settings with modifiers', function (string $prefix): void {
    config()->set('opensearch-migrations.index_name_prefix', $prefix);

    $indexManager = new FakeIndexManager;
    $adapter = new IndexManagerAdapter($indexManager);

    $adapter->putSettings('test', function (Settings $settings): void {
        $settings->index(['number_of_replicas' => 2, 'refresh_interval' => -1]);
    });

    $indexManager->assertSettingsPut(
        $prefix.'test',
        (new Settings)->index(['number_of_replicas' => 2, 'refresh_interval' => -1])
    );
})->with('prefixes');

it('pushes settings with modifiers', function (string $prefix): void {
    config()->set('opensearch-migrations.index_name_prefix', $prefix);

    $indexManager = new FakeIndexManager;
    $adapter = new IndexManagerAdapter($indexManager);

    $adapter->pushSettings('test', function (Settings $settings): void {
        $settings->index(['number_of_replicas' => 2]);
    });

    $indexManager
        ->assertClosed($prefix.'test')
        ->assertSettingsPut($prefix.'test', (new Settings)->index(['number_of_replicas' => 2]))
        ->assertOpened($prefix.'test');
})->with('prefixes');

it('drops indexes', function (string $prefix): void {
    config()->set('opensearch-migrations.index_name_prefix', $prefix);

    $indexManager = new FakeIndexManager;
    $adapter = new IndexManagerAdapter($indexManager);

    $adapter->drop('test');

    $indexManager->assertDeleted($prefix.'test');
})->with('prefixes');

it('drops indexes only when they exist', function (string $prefix): void {
    config()->set('opensearch-migrations.index_name_prefix', $prefix);

    $indexManager = new FakeIndexManager(existing: [$prefix.'test']);
    $adapter = new IndexManagerAdapter($indexManager);

    $adapter->dropIfExists('test');

    $indexManager
        ->assertChecked($prefix.'test')
        ->assertDeleted($prefix.'test');
})->with('prefixes');

it('creates aliases', function (string $prefix): void {
    config()->set('opensearch-migrations.index_name_prefix', $prefix);
    config()->set('opensearch-migrations.alias_name_prefix', $prefix);

    $indexManager = new FakeIndexManager;
    $adapter = new IndexManagerAdapter($indexManager);

    $adapter->putAlias('foo', 'bar');

    $indexManager->assertAliasPut($prefix.'foo', new Alias($prefix.'bar'));
})->with('prefixes');

it('deletes aliases', function (string $prefix): void {
    config()->set('opensearch-migrations.index_name_prefix', $prefix);
    config()->set('opensearch-migrations.alias_name_prefix', $prefix);

    $indexManager = new FakeIndexManager;
    $adapter = new IndexManagerAdapter($indexManager);

    $adapter->deleteAlias('foo', 'bar');

    $indexManager->assertAliasDeleted($prefix.'foo', $prefix.'bar');
})->with('prefixes');

dataset('prefixes', [
    'no prefix' => [''],
    'short prefix' => ['foo_'],
    'long prefix' => ['foo_bar_'],
]);
