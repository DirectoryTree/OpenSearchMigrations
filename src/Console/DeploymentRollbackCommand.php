<?php

namespace DirectoryTree\OpenSearchMigrations\Console;

use DirectoryTree\OpenSearchMigrations\Deployer;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * Roll back an OpenSearch deployment.
 */
class DeploymentRollbackCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opensearch:deploy:rollback
        {name : The logical index name}
        {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore the previous index behind an alias';

    /**
     * Execute the console command.
     */
    public function handle(Deployer $deployer): int
    {
        if (! $this->confirmToProceed()) {
            return static::FAILURE;
        }

        $deployer->rollback($this->argument('name'));

        $this->components->info("Deployment [{$this->argument('name')}] was rolled back.");

        return static::SUCCESS;
    }
}
