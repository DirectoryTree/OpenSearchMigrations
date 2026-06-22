<?php

namespace DirectoryTree\OpenSearchMigrations;

use DirectoryTree\OpenSearchAdapter\Indices\IndexManager;
use DirectoryTree\OpenSearchAdapter\Indices\IndexManagerInterface as AdapterIndexManagerInterface;
use DirectoryTree\OpenSearchClient\OpenSearchManager;
use DirectoryTree\OpenSearchMigrations\Adapters\IndexManagerAdapter;
use DirectoryTree\OpenSearchMigrations\Console\FreshCommand;
use DirectoryTree\OpenSearchMigrations\Console\MakeCommand;
use DirectoryTree\OpenSearchMigrations\Console\MigrateCommand;
use DirectoryTree\OpenSearchMigrations\Console\RefreshCommand;
use DirectoryTree\OpenSearchMigrations\Console\ResetCommand;
use DirectoryTree\OpenSearchMigrations\Console\RollbackCommand;
use DirectoryTree\OpenSearchMigrations\Console\StatusCommand;
use DirectoryTree\OpenSearchMigrations\Filesystem\MigrationStorage;
use DirectoryTree\OpenSearchMigrations\Repositories\MigrationRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the OpenSearch migrations package.
 */
class OpenSearchMigrationsServiceProvider extends ServiceProvider
{
    /**
     * The package commands.
     *
     * @var array<int, class-string>
     */
    protected array $commands = [
        MakeCommand::class,
        ResetCommand::class,
        FreshCommand::class,
        StatusCommand::class,
        MigrateCommand::class,
        RefreshCommand::class,
        RollbackCommand::class,
    ];

    /**
     * Register the package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/opensearch-migrations.php', 'opensearch-migrations');

        $this->app->bind(IndexManagerInterface::class, IndexManagerAdapter::class);

        $this->app->bind(MigrationRepository::class, function (Application $app) {
            return new MigrationRepository(
                $app['config']->get('opensearch-migrations.table'),
                $app['config']->get('opensearch-migrations.connection')
            );
        });

        $this->app->bind(MigrationStorage::class, function (Application $app) {
            return new MigrationStorage(
                $app->make(Filesystem::class),
                $app['config']->get('opensearch-migrations.storage_directory')
            );
        });

        $this->app->singleton(IndexManager::class, function (Application $app) {
            return new IndexManager($app->make(OpenSearchManager::class)->default());
        });

        $this->app->bind(AdapterIndexManagerInterface::class, IndexManager::class);
    }

    /**
     * Bootstrap the package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/opensearch-migrations.php' => config_path('opensearch-migrations.php'),
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->commands($this->commands);
    }
}
