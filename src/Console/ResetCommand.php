<?php

namespace DirectoryTree\OpenSearchMigrations\Console;

use DirectoryTree\OpenSearchMigrations\Migrator;
use DirectoryTree\OpenSearchMigrations\Repositories\DeploymentRepository;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * Roll back all OpenSearch migrations.
 */
class ResetCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opensearch:migrate:reset 
        {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback all migrations';

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
            $this->components->error('Managed indexes cannot be reset. Use opensearch:migrate:fresh to discard them.');

            return static::FAILURE;
        }

        $migrator->rollbackAll();

        return 0;
    }
}
