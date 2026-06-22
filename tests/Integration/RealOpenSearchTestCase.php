<?php

namespace DirectoryTree\OpenSearchMigrations\Tests\Integration;

use DirectoryTree\OpenSearchClient\OpenSearchClientServiceProvider;
use DirectoryTree\OpenSearchClient\OpenSearchManager;
use DirectoryTree\OpenSearchMigrations\OpenSearchMigrationsServiceProvider;
use OpenSearch\Client;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

class RealOpenSearchTestCase extends TestbenchTestCase
{
    public string $indexPrefix;

    protected function getPackageProviders($app): array
    {
        return [
            OpenSearchClientServiceProvider::class,
            OpenSearchMigrationsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('opensearch-client', [
            'default' => 'default',
            'connections' => [
                'default' => [
                    'base_uri' => env('OPENSEARCH_HOST', 'http://127.0.0.1:9200'),
                ],
            ],
        ]);

        $app['config']->set('opensearch-migrations.table', 'real_opensearch_migrations');
        $app['config']->set('opensearch-migrations.storage_directory', realpath(__DIR__.'/../real_migrations'));

        $app->bind(Client::class, function ($app) {
            return $app->make(OpenSearchManager::class)->default();
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->indexPrefix = sprintf('migrations_integration_%s_', bin2hex(random_bytes(4)));

        $this->app['config']->set('opensearch-migrations.index_name_prefix', $this->indexPrefix);
        $this->app['config']->set('opensearch-migrations.alias_name_prefix', $this->indexPrefix);
    }

    protected function tearDown(): void
    {
        $this->dropIndexIfExists($this->indexPrefix.'real_test');

        parent::tearDown();
    }

    public function client(): Client
    {
        return $this->app->make(Client::class);
    }

    public function dropIndexIfExists(string $index): void
    {
        if ($this->client()->indices()->exists(['index' => $index])) {
            $this->client()->indices()->delete(['index' => $index]);
        }
    }
}
