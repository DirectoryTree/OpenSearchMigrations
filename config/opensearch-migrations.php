<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Migration Table
    |--------------------------------------------------------------------------
    |
    | This table stores OpenSearch migration file names and batch numbers.
    |
    */

    'table' => env('OPENSEARCH_MIGRATIONS_TABLE', 'opensearch_migrations'),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | This connection stores the OpenSearch migration table. When null, the
    | application's default database connection will be used.
    |
    */

    'connection' => env('OPENSEARCH_MIGRATIONS_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Migration Directory
    |--------------------------------------------------------------------------
    |
    | This directory contains the OpenSearch migration files that should be
    | discovered and executed by the migration commands.
    |
    */

    'storage_directory' => env('OPENSEARCH_MIGRATIONS_DIRECTORY', base_path('opensearch/migrations')),

    /*
    |--------------------------------------------------------------------------
    | Name Prefixes
    |--------------------------------------------------------------------------
    |
    | Prefixes are applied to index and alias names before OpenSearch receives
    | them. The Scout prefix is used by default to keep search resources aligned.
    |
    */

    'index_name_prefix' => env('OPENSEARCH_MIGRATIONS_INDEX_NAME_PREFIX', env('SCOUT_PREFIX', '')),

    'alias_name_prefix' => env('OPENSEARCH_MIGRATIONS_ALIAS_NAME_PREFIX', env('SCOUT_PREFIX', '')),
];
