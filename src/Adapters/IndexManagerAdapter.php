<?php

namespace DirectoryTree\OpenSearchMigrations\Adapters;

use DirectoryTree\OpenSearchAdapter\Indices\Alias;
use DirectoryTree\OpenSearchAdapter\Indices\IndexBlueprint;
use DirectoryTree\OpenSearchAdapter\Indices\IndexManager;
use DirectoryTree\OpenSearchAdapter\Indices\Mapping;
use DirectoryTree\OpenSearchAdapter\Indices\Settings;
use DirectoryTree\OpenSearchMigrations\IndexManagerInterface;

use function DirectoryTree\OpenSearchMigrations\prefix_alias_name;
use function DirectoryTree\OpenSearchMigrations\prefix_index_name;

/**
 * Adapts the OpenSearch index manager for migration-friendly operations.
 */
class IndexManagerAdapter implements IndexManagerInterface
{
    /**
     * Create a new index manager adapter instance.
     */
    public function __construct(protected IndexManager $indexManager) {}

    /**
     * Create a new index with an optional mapping and settings modifier.
     */
    public function create(string $indexName, ?callable $modifier = null): IndexManagerInterface
    {
        $prefixedIndexName = prefix_index_name($indexName);

        if (isset($modifier)) {
            $mapping = new Mapping;
            $settings = new Settings;

            $modifier($mapping, $settings);

            $index = new IndexBlueprint($prefixedIndexName, $mapping, $settings);
        } else {
            $index = new IndexBlueprint($prefixedIndexName);
        }

        $this->indexManager->create($index);

        return $this;
    }

    /**
     * Create a new index with raw mapping and settings arrays.
     *
     * @param  array<string, mixed>|null  $mapping
     * @param  array<string, mixed>|null  $settings
     */
    public function createRaw(string $indexName, ?array $mapping = null, ?array $settings = null): IndexManagerInterface
    {
        $prefixedIndexName = prefix_index_name($indexName);

        $this->indexManager->createRaw($prefixedIndexName, $mapping, $settings);

        return $this;
    }

    /**
     * Create a new index when it does not already exist.
     */
    public function createIfNotExists(string $indexName, ?callable $modifier = null): IndexManagerInterface
    {
        $prefixedIndexName = prefix_index_name($indexName);

        if (! $this->indexManager->exists($prefixedIndexName)) {
            $this->create($indexName, $modifier);
        }

        return $this;
    }

    /**
     * Create a new index from raw mapping and settings arrays when it does not already exist.
     *
     * @param  array<string, mixed>|null  $mapping
     * @param  array<string, mixed>|null  $settings
     */
    public function createIfNotExistsRaw(
        string $indexName,
        ?array $mapping = null,
        ?array $settings = null
    ): IndexManagerInterface {
        $prefixedIndexName = prefix_index_name($indexName);

        if (! $this->indexManager->exists($prefixedIndexName)) {
            $this->createRaw($indexName, $mapping, $settings);
        }

        return $this;
    }

    /**
     * Update an index mapping with a mapping modifier.
     */
    public function putMapping(string $indexName, callable $modifier): IndexManagerInterface
    {
        $prefixedIndexName = prefix_index_name($indexName);

        $mapping = new Mapping;
        $modifier($mapping);

        $this->indexManager->putMapping($prefixedIndexName, $mapping);

        return $this;
    }

    /**
     * Update an index mapping from a raw mapping array.
     *
     * @param  array<string, mixed>  $mapping
     */
    public function putMappingRaw(string $indexName, array $mapping): IndexManagerInterface
    {
        $prefixedIndexName = prefix_index_name($indexName);

        $this->indexManager->putMappingRaw($prefixedIndexName, $mapping);

        return $this;
    }

    /**
     * Update index settings with a settings modifier.
     */
    public function putSettings(string $indexName, callable $modifier): IndexManagerInterface
    {
        $prefixedIndexName = prefix_index_name($indexName);

        $settings = new Settings;
        $modifier($settings);

        $this->indexManager->putSettings($prefixedIndexName, $settings);

        return $this;
    }

    /**
     * Update index settings from a raw settings array.
     *
     * @param  array<string, mixed>  $settings
     */
    public function putSettingsRaw(string $indexName, array $settings): IndexManagerInterface
    {
        $prefixedIndexName = prefix_index_name($indexName);

        $this->indexManager->putSettingsRaw($prefixedIndexName, $settings);

        return $this;
    }

    /**
     * Close the index, update its settings, and re-open it.
     */
    public function pushSettings(string $indexName, callable $modifier): IndexManagerInterface
    {
        $prefixedIndexName = prefix_index_name($indexName);

        $this->indexManager->close($prefixedIndexName);
        $this->putSettings($indexName, $modifier);
        $this->indexManager->open($prefixedIndexName);

        return $this;
    }

    /**
     * Close the index, update its settings from a raw array, and re-open it.
     *
     * @param  array<string, mixed>  $settings
     */
    public function pushSettingsRaw(string $indexName, array $settings): IndexManagerInterface
    {
        $prefixedIndexName = prefix_index_name($indexName);

        $this->indexManager->close($prefixedIndexName);
        $this->putSettingsRaw($indexName, $settings);
        $this->indexManager->open($prefixedIndexName);

        return $this;
    }

    /**
     * Delete an index.
     */
    public function drop(string $indexName): IndexManagerInterface
    {
        $prefixedIndexName = prefix_index_name($indexName);

        $this->indexManager->drop($prefixedIndexName);

        return $this;
    }

    /**
     * Delete an index when it exists.
     */
    public function dropIfExists(string $indexName): IndexManagerInterface
    {
        $prefixedIndexName = prefix_index_name($indexName);

        if ($this->indexManager->exists($prefixedIndexName)) {
            $this->drop($indexName);
        }

        return $this;
    }

    /**
     * Create or update an index alias.
     *
     * @param  array<string, mixed>|null  $filter
     */
    public function putAlias(string $indexName, string $aliasName, ?array $filter = null): IndexManagerInterface
    {
        $prefixedIndexName = prefix_index_name($indexName);
        $prefixedAliasName = prefix_alias_name($aliasName);

        $this->indexManager->putAlias($prefixedIndexName, new Alias($prefixedAliasName, $filter));

        return $this;
    }

    /**
     * Delete an index alias.
     */
    public function deleteAlias(string $indexName, string $aliasName): IndexManagerInterface
    {
        $prefixedIndexName = prefix_index_name($indexName);
        $prefixedAliasName = prefix_alias_name($aliasName);

        $this->indexManager->deleteAlias($prefixedIndexName, $prefixedAliasName);

        return $this;
    }
}
