<?php

namespace DirectoryTree\OpenSearchMigrations\Tests\Integration;

use DirectoryTree\OpenSearchClient\ClientBuilderInterface;
use DirectoryTree\OpenSearchClient\OpenSearchClientServiceProvider;
use DirectoryTree\OpenSearchMigrations\OpenSearchMigrationsServiceProvider;
use OpenSearch\Client;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

class TestCase extends TestbenchTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            OpenSearchClientServiceProvider::class,
            OpenSearchMigrationsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('opensearch.migrations.table', 'test_opensearch_migrations');
        $app['config']->set('opensearch.migrations.storage_directory', realpath(__DIR__.'/../migrations'));

        $client = $this->createMock(Client::class);
        $builder = $this->createMock(ClientBuilderInterface::class);
        $builder->method('default')->willReturn($client);

        $app->instance(Client::class, $client);
        $app->instance(ClientBuilderInterface::class, $builder);
    }
}
