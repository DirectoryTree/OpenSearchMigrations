<?php

namespace DirectoryTree\OpenSearchMigrations\Console;

use DirectoryTree\OpenSearchMigrations\Deployer;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * Retire an OpenSearch deployment.
 */
class DeploymentRetireCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opensearch:deploy:retire
        {name : The logical index name}
        {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the previous index and complete a deployment';

    /**
     * Execute the console command.
     */
    public function handle(Deployer $deployer): int
    {
        if (! $this->confirmToProceed()) {
            return static::FAILURE;
        }

        $deployer->retire($this->argument('name'));

        $this->components->info("Deployment [{$this->argument('name')}] was retired.");

        return static::SUCCESS;
    }
}
