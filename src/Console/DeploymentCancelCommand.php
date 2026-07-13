<?php

namespace DirectoryTree\OpenSearchMigrations\Console;

use DirectoryTree\OpenSearchMigrations\Deployer;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * Cancel an OpenSearch deployment.
 */
class DeploymentCancelCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opensearch:deploy:cancel
        {name : The logical index name}
        {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel an index deployment and delete its candidate';

    /**
     * Execute the console command.
     */
    public function handle(Deployer $deployer): int
    {
        if (! $this->confirmToProceed()) {
            return static::FAILURE;
        }

        $deployer->cancel($this->argument('name'));

        $this->components->info("Deployment [{$this->argument('name')}] was cancelled.");

        return static::SUCCESS;
    }
}
