<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Deployment Table
    |--------------------------------------------------------------------------
    |
    | This table stores the state of versioned OpenSearch index deployments.
    |
    */

    'table' => env('OPENSEARCH_DEPLOYMENTS_TABLE', 'opensearch_deployments'),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | This connection stores OpenSearch deployment state. When null, the
    | application's default database connection will be used.
    |
    */

    'connection' => env(
        'OPENSEARCH_DEPLOYMENTS_CONNECTION',
        env('OPENSEARCH_MIGRATIONS_CONNECTION')
    ),
];
