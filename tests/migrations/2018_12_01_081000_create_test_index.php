<?php

use DirectoryTree\OpenSearchMigrations\Facades\Index;
use DirectoryTree\OpenSearchMigrations\MigrationInterface;
use DirectoryTree\OpenSearchMigrations\Support\Prefix;
use OpenSearch\Client;

class CreateTestIndex implements MigrationInterface
{
    /**
     * Create a new test index migration instance.
     */
    public function __construct(
        protected Client $client
    ) {}

    public function up(): void
    {
        Index::create('test');

        $this->client->indices()->clearCache([
            'index' => Prefix::index('test'),
        ]);
    }

    public function down(): void
    {
        Index::drop('test');
    }
}
