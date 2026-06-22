<?php

namespace DirectoryTree\OpenSearchMigrations\Testing\Fakes;

use DirectoryTree\OpenSearchAdapter\Indices\Alias;
use DirectoryTree\OpenSearchAdapter\Indices\IndexBlueprint;
use DirectoryTree\OpenSearchAdapter\Indices\Mapping;
use DirectoryTree\OpenSearchAdapter\Indices\Settings;
use DirectoryTree\OpenSearchAdapter\Testing\Fakes\FakeIndexManager as AdapterFakeIndexManager;
use DirectoryTree\OpenSearchMigrations\Adapters\IndexManagerAdapter;
use DirectoryTree\OpenSearchMigrations\IndexManagerInterface;
use DirectoryTree\OpenSearchMigrations\Support\MigrationPrefix;

/**
 * Fakes OpenSearch migration index operations for tests.
 */
class FakeIndexManager implements IndexManagerInterface
{
    /**
     * The migration index manager adapter.
     */
    protected IndexManagerAdapter $adapter;

    /**
     * The underlying adapter fake index manager.
     */
    protected AdapterFakeIndexManager $manager;

    /**
     * Create a new fake index manager instance.
     *
     * @param  array<int, string>  $existing
     */
    public function __construct(array $existing = [])
    {
        $this->manager = new AdapterFakeIndexManager(
            existing: array_map(MigrationPrefix::index(...), $existing)
        );

        $this->adapter = new IndexManagerAdapter($this->manager);
    }

    /**
     * Create a new index with an optional mapping and settings modifier.
     */
    public function create(string $index, ?callable $modifier = null): static
    {
        $this->adapter->create($index, $modifier);

        return $this;
    }

    /**
     * Create a new index when it does not already exist.
     */
    public function createIfNotExists(string $index, ?callable $modifier = null): static
    {
        $this->adapter->createIfNotExists($index, $modifier);

        return $this;
    }

    /**
     * Update an index mapping with a mapping modifier.
     */
    public function putMapping(string $index, callable $modifier): static
    {
        $this->adapter->putMapping($index, $modifier);

        return $this;
    }

    /**
     * Update index settings with a settings modifier.
     */
    public function putSettings(string $index, callable $modifier): static
    {
        $this->adapter->putSettings($index, $modifier);

        return $this;
    }

    /**
     * Close the index, update its settings, and re-open it.
     */
    public function pushSettings(string $index, callable $modifier): static
    {
        $this->adapter->pushSettings($index, $modifier);

        return $this;
    }

    /**
     * Delete an index.
     */
    public function drop(string $index): static
    {
        $this->adapter->drop($index);

        return $this;
    }

    /**
     * Delete an index when it exists.
     */
    public function dropIfExists(string $index): static
    {
        $this->adapter->dropIfExists($index);

        return $this;
    }

    /**
     * Create or update an index alias.
     *
     * @param  array<string, mixed>|null  $filter
     */
    public function putAlias(string $index, string $aliasName, ?array $filter = null): static
    {
        $this->adapter->putAlias($index, $aliasName, $filter);

        return $this;
    }

    /**
     * Delete an index alias.
     */
    public function deleteAlias(string $index, string $aliasName): static
    {
        $this->adapter->deleteAlias($index, $aliasName);

        return $this;
    }

    /**
     * Assert that the given index existence was checked.
     */
    public function assertChecked(string $index): static
    {
        $this->manager->assertChecked(MigrationPrefix::index($index));

        return $this;
    }

    /**
     * Assert that the given index was created.
     */
    public function assertCreated(string $index, ?callable $modifier = null): static
    {
        $this->manager->assertCreated($this->blueprint($index, $modifier));

        return $this;
    }

    /**
     * Assert that the given index was not created.
     */
    public function assertNotCreated(string $index): static
    {
        $this->manager->assertNotCreated(MigrationPrefix::index($index));

        return $this;
    }

    /**
     * Assert that the given index mapping was updated.
     */
    public function assertMappingPut(string $index, callable $modifier): static
    {
        $modifier($mapping = new Mapping);

        $this->manager->assertMappingPut(MigrationPrefix::index($index), $mapping);

        return $this;
    }

    /**
     * Assert that the given index settings were updated.
     */
    public function assertSettingsPut(string $index, callable $modifier): static
    {
        $modifier($settings = new Settings);

        $this->manager->assertSettingsPut(MigrationPrefix::index($index), $settings);

        return $this;
    }

    /**
     * Assert that the given index was opened.
     */
    public function assertOpened(string $index): static
    {
        $this->manager->assertOpened(MigrationPrefix::index($index));

        return $this;
    }

    /**
     * Assert that the given index was closed.
     */
    public function assertClosed(string $index): static
    {
        $this->manager->assertClosed(MigrationPrefix::index($index));

        return $this;
    }

    /**
     * Assert that the given index was deleted.
     */
    public function assertDeleted(string $index): static
    {
        $this->manager->assertDeleted(MigrationPrefix::index($index));

        return $this;
    }

    /**
     * Assert that the given alias was put on an index.
     *
     * @param  array<string, mixed>|null  $filter
     */
    public function assertAliasPut(string $index, string $alias, ?array $filter = null): static
    {
        $this->manager->assertAliasPut(
            MigrationPrefix::index($index),
            new Alias(MigrationPrefix::alias($alias), $filter),
        );

        return $this;
    }

    /**
     * Assert that the given alias was deleted from an index.
     */
    public function assertAliasDeleted(string $index, string $alias): static
    {
        $this->manager->assertAliasDeleted(
            MigrationPrefix::index($index),
            MigrationPrefix::alias($alias),
        );

        return $this;
    }

    /**
     * Create an index blueprint for an assertion.
     */
    protected function blueprint(string $index, ?callable $modifier = null): IndexBlueprint
    {
        if (isset($modifier)) {
            $modifier(
                $mapping = new Mapping,
                $settings = new Settings,
            );

            return new IndexBlueprint(MigrationPrefix::index($index), $mapping, $settings);
        }

        return new IndexBlueprint(MigrationPrefix::index($index));
    }
}
