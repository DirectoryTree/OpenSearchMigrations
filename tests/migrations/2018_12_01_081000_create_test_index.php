<?php

use DirectoryTree\OpenSearchMigrations\Facades\Index;
use DirectoryTree\OpenSearchMigrations\MigrationInterface;
use OpenSearch\Client;

class CreateTestIndex implements MigrationInterface
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function up(): void
    {
        Index::create('test');

        $this->client->indices()->clearCache([
            'index' => 'test',
        ]);
    }

    public function down(): void
    {
        Index::drop('test');
    }
}
