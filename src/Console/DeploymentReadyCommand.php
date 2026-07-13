<?php

namespace DirectoryTree\OpenSearchMigrations\Console;

use DirectoryTree\OpenSearchMigrations\Deployer;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * Mark an OpenSearch deployment as ready.
 */
class DeploymentReadyCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opensearch:deploy:ready
        {name : The logical index name}
        {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark an index deployment as ready for cutover';

    /**
     * Execute the console command.
     */
    public function handle(Deployer $deployer): int
    {
        if (! $this->confirmToProceed()) {
            return static::FAILURE;
        }

        $deployer->markReady($this->argument('name'));

        $this->components->info("Deployment [{$this->argument('name')}] is ready for cutover.");

        return static::SUCCESS;
    }
}
