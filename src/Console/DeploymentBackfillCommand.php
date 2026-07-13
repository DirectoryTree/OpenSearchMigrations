<?php

namespace DirectoryTree\OpenSearchMigrations\Console;

use DirectoryTree\OpenSearchMigrations\Deployer;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * Begin backfilling an OpenSearch deployment.
 */
class DeploymentBackfillCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opensearch:deploy:backfill
        {name : The logical index name}
        {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable candidate writes before backfilling an index';

    /**
     * Execute the console command.
     */
    public function handle(Deployer $deployer): int
    {
        if (! $this->confirmToProceed()) {
            return static::FAILURE;
        }

        $deployer->backfill($this->argument('name'));

        $this->components->info("Deployment [{$this->argument('name')}] is ready to be backfilled.");

        return static::SUCCESS;
    }
}
