<?php

namespace DirectoryTree\OpenSearchMigrations;

/**
 * Manage OpenSearch indices and aliases used by migrations.
 */
interface IndexManagerInterface
{
    /**
     * Create a new index with an optional mapping and settings modifier.
     */
    public function create(string $indexName, ?callable $modifier = null): self;

    /**
     * Create a new index when it does not already exist.
     */
    public function createIfNotExists(string $indexName, ?callable $modifier = null): self;

    /**
     * Update an index mapping with a mapping modifier.
     */
    public function putMapping(string $indexName, callable $modifier): self;

    /**
     * Update index settings with a settings modifier.
     */
    public function putSettings(string $indexName, callable $modifier): self;

    /**
     * Close the index, update its settings, and re-open it.
     */
    public function pushSettings(string $indexName, callable $modifier): self;

    /**
     * Delete an index.
     */
    public function drop(string $indexName): self;

    /**
     * Delete an index when it exists.
     */
    public function dropIfExists(string $indexName): self;

    /**
     * Create or update an index alias.
     *
     * @param  array<string, mixed>|null  $filter
     */
    public function putAlias(string $indexName, string $aliasName, ?array $filter = null): self;

    /**
     * Delete an index alias.
     */
    public function deleteAlias(string $indexName, string $aliasName): self;
}
