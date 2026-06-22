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
    public function create(string $index, ?callable $modifier = null): static;

    /**
     * Create a new index when it does not already exist.
     */
    public function createIfNotExists(string $index, ?callable $modifier = null): static;

    /**
     * Update an index mapping with a mapping modifier.
     */
    public function putMapping(string $index, callable $modifier): static;

    /**
     * Update index settings with a settings modifier.
     */
    public function putSettings(string $index, callable $modifier): static;

    /**
     * Close the index, update its settings, and re-open it.
     */
    public function pushSettings(string $index, callable $modifier): static;

    /**
     * Delete an index.
     */
    public function drop(string $index): static;

    /**
     * Delete an index when it exists.
     */
    public function dropIfExists(string $index): static;

    /**
     * Create or update an index alias.
     *
     * @param  array<string, mixed>|null  $filter
     */
    public function putAlias(string $index, string $aliasName, ?array $filter = null): static;

    /**
     * Delete an index alias.
     */
    public function deleteAlias(string $index, string $aliasName): static;
}
