<?php

namespace DirectoryTree\OpenSearchMigrations;

use DirectoryTree\OpenSearchAdapter\Indices\IndexManager;
use DirectoryTree\OpenSearchClient\ClientBuilderInterface;
use DirectoryTree\OpenSearchMigrations\Adapters\IndexManagerAdapter;
use DirectoryTree\OpenSearchMigrations\Console\FreshCommand;
use DirectoryTree\OpenSearchMigrations\Console\MakeCommand;
use DirectoryTree\OpenSearchMigrations\Console\MigrateCommand;
use DirectoryTree\OpenSearchMigrations\Console\RefreshCommand;
use DirectoryTree\OpenSearchMigrations\Console\ResetCommand;
use DirectoryTree\OpenSearchMigrations\Console\RollbackCommand;
use DirectoryTree\OpenSearchMigrations\Console\StatusCommand;
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
        MigrateCommand::class,
        RefreshCommand::class,
        ResetCommand::class,
        RollbackCommand::class,
        StatusCommand::class,
        FreshCommand::class,
    ];

    /**
     * Register the package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/opensearch.migrations.php', 'opensearch.migrations');

        $this->app->bindIf(IndexManagerInterface::class, IndexManagerAdapter::class);

        $this->app->singletonIf(IndexManager::class, function ($app) {
            return new IndexManager($app->make(ClientBuilderInterface::class)->default());
        });
    }

    /**
     * Bootstrap the package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/opensearch.migrations.php' => config_path('opensearch.migrations.php'),
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->commands($this->commands);
    }
}
