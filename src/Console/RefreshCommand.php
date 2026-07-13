<?php

namespace DirectoryTree\OpenSearchMigrations\Console;

use DirectoryTree\OpenSearchMigrations\Migrator;
use DirectoryTree\OpenSearchMigrations\Repositories\DeploymentRepository;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * Roll back and re-run all OpenSearch migrations.
 */
class RefreshCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opensearch:migrate:refresh 
        {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset and re-run all migrations';

    /**
     * Execute the console command.
     */
    public function handle(Migrator $migrator, DeploymentRepository $deployments): int
    {
        $migrator->setOutput($this->output);

        if (! $this->confirmToProceed()) {
            return 1;
        }

        $migrator->prepare();
        $deployments->prepare();

        if ($deployments->exists()) {
            $this->components->error('Managed indexes cannot be refreshed. Use opensearch:migrate:fresh to discard them.');

            return static::FAILURE;
        }

        $migrator->rollbackAll();
        $migrator->migrateAll();

        return 0;
    }
}
