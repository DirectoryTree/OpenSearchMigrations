<?php

namespace DirectoryTree\OpenSearchMigrations;

/**
 * Prefix an OpenSearch index name.
 */
function prefix_index_name(string $indexName): string
{
    return config('opensearch-migrations.index_name_prefix').$indexName;
}

/**
 * Prefix an OpenSearch alias name.
 */
function prefix_alias_name(string $aliasName): string
{
    return config('opensearch-migrations.alias_name_prefix').$aliasName;
}
