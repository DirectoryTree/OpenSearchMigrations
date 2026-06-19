<?php

namespace DirectoryTree\OpenSearchMigrations;

function prefix_index_name(string $indexName): string
{
    return config('opensearch.migrations.index_name_prefix').$indexName;
}

function prefix_alias_name(string $aliasName): string
{
    return config('opensearch.migrations.alias_name_prefix').$aliasName;
}
