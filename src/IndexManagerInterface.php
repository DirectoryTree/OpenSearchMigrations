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
     * Create a new index with raw mapping and settings arrays.
     *
     * @param  array<string, mixed>|null  $mapping
     * @param  array<string, mixed>|null  $settings
     */
    public function createRaw(string $indexName, ?array $mapping = null, ?array $settings = null): self;

    /**
     * Create a new index when it does not already exist.
     */
    public function createIfNotExists(string $indexName, ?callable $modifier = null): self;

    /**
     * Create a new index from raw mapping and settings arrays when it does not already exist.
     *
     * @param  array<string, mixed>|null  $mapping
     * @param  array<string, mixed>|null  $settings
     */
    public function createIfNotExistsRaw(string $indexName, ?array $mapping = null, ?array $settings = null): self;

    /**
     * Update an index mapping with a mapping modifier.
     */
    public function putMapping(string $indexName, callable $modifier): self;

    /**
     * Update an index mapping from a raw mapping array.
     *
     * @param  array<string, mixed>  $mapping
     */
    public function putMappingRaw(string $indexName, array $mapping): self;

    /**
     * Update index settings with a settings modifier.
     */
    public function putSettings(string $indexName, callable $modifier): self;

    /**
     * Update index settings from a raw settings array.
     *
     * @param  array<string, mixed>  $settings
     */
    public function putSettingsRaw(string $indexName, array $settings): self;

    /**
     * Close the index, update its settings, and re-open it.
     */
    public function pushSettings(string $indexName, callable $modifier): self;

    /**
     * Close the index, update its settings from a raw array, and re-open it.
     *
     * @param  array<string, mixed>  $settings
     */
    public function pushSettingsRaw(string $indexName, array $settings): self;

    /**
     * Delete an index.
     */
    public function drop(string $indexName): self;

    /**
     * Delete an index when it exists.
     */
    public function dropIfExists(string $indexName): self;
}
