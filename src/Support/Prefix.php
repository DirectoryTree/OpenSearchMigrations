<?php

namespace DirectoryTree\OpenSearchMigrations\Support;

/**
 * Applies configured OpenSearch migration prefixes.
 */
class Prefix
{
    /**
     * Prefix an OpenSearch index name.
     */
    public static function index(string $index): string
    {
        return config('opensearch-migrations.index_name_prefix').$index;
    }

    /**
     * Prefix an OpenSearch alias name.
     */
    public static function alias(string $alias): string
    {
        return config('opensearch-migrations.alias_name_prefix').$alias;
    }
}
