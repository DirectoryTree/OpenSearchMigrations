<?php

namespace DirectoryTree\OpenSearchMigrations\Adapters;

use DirectoryTree\OpenSearchAdapter\Indices\Alias;
use DirectoryTree\OpenSearchAdapter\Indices\IndexBlueprint;
use DirectoryTree\OpenSearchAdapter\Indices\IndexManager;
use DirectoryTree\OpenSearchAdapter\Indices\Mapping;
use DirectoryTree\OpenSearchAdapter\Indices\Settings;
use DirectoryTree\OpenSearchMigrations\IndexManagerInterface;
use DirectoryTree\OpenSearchMigrations\Support\MigrationPrefix;

/**
 * Adapts the OpenSearch index manager for migration-friendly operations.
 */
class IndexManagerAdapter implements IndexManagerInterface
{
    /**
     * Create a new index manager adapter instance.
     */
    public function __construct(
        protected IndexManager $indexManager
    ) {}

    /**
     * Create a new index with an optional mapping and settings modifier.
     */
    public function create(string $index, ?callable $modifier = null): static
    {
        $prefixedIndex = MigrationPrefix::index($index);

        if (isset($modifier)) {
            $modifier(
                $mapping = new Mapping,
                $settings = new Settings,
            );

            $blueprint = new IndexBlueprint($prefixedIndex, $mapping, $settings);
        } else {
            $blueprint = new IndexBlueprint($prefixedIndex);
        }

        $this->indexManager->create($blueprint);

        return $this;
    }

    /**
     * Create a new index when it does not already exist.
     */
    public function createIfNotExists(string $index, ?callable $modifier = null): static
    {
        $prefixedIndex = MigrationPrefix::index($index);

        if (! $this->indexManager->exists($prefixedIndex)) {
            $this->create($index, $modifier);
        }

        return $this;
    }

    /**
     * Update an index mapping with a mapping modifier.
     */
    public function putMapping(string $index, callable $modifier): static
    {
        $prefixedIndex = MigrationPrefix::index($index);

        $modifier($mapping = new Mapping);

        $this->indexManager->putMapping($prefixedIndex, $mapping);

        return $this;
    }

    /**
     * Update index settings with a settings modifier.
     */
    public function putSettings(string $index, callable $modifier): static
    {
        $prefixedIndex = MigrationPrefix::index($index);

        $modifier($settings = new Settings);

        $this->indexManager->putSettings($prefixedIndex, $settings);

        return $this;
    }

    /**
     * Close the index, update its settings, and re-open it.
     */
    public function pushSettings(string $index, callable $modifier): static
    {
        $prefixedIndex = MigrationPrefix::index($index);

        $this->indexManager->close($prefixedIndex);
        $this->putSettings($index, $modifier);
        $this->indexManager->open($prefixedIndex);

        return $this;
    }

    /**
     * Delete an index.
     */
    public function drop(string $index): static
    {
        $prefixedIndex = MigrationPrefix::index($index);

        $this->indexManager->delete($prefixedIndex);

        return $this;
    }

    /**
     * Delete an index when it exists.
     */
    public function dropIfExists(string $index): static
    {
        $prefixedIndex = MigrationPrefix::index($index);

        if ($this->indexManager->exists($prefixedIndex)) {
            $this->drop($index);
        }

        return $this;
    }

    /**
     * Create or update an index alias.
     *
     * @param  array<string, mixed>|null  $filter
     */
    public function putAlias(string $index, string $aliasName, ?array $filter = null): static
    {
        $this->indexManager->putAlias(
            MigrationPrefix::index($index),
            new Alias(MigrationPrefix::alias($aliasName), $filter),
        );

        return $this;
    }

    /**
     * Delete an index alias.
     */
    public function deleteAlias(string $index, string $aliasName): static
    {
        $this->indexManager->deleteAlias(
            MigrationPrefix::index($index),
            MigrationPrefix::alias($aliasName),
        );

        return $this;
    }
}
