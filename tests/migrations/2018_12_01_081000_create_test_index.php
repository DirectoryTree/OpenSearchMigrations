<?php

use DirectoryTree\OpenSearchMigrations\Facades\Index;
use DirectoryTree\OpenSearchMigrations\MigrationInterface;
use OpenSearch\Client;

use function DirectoryTree\OpenSearchMigrations\prefix_index_name;

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
            'index' => prefix_index_name('test'),
        ]);
    }

    public function down(): void
    {
        Index::drop('test');
    }
}
