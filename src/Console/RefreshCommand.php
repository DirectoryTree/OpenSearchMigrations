<?php

namespace DirectoryTree\OpenSearchMigrations\Console;

use DirectoryTree\OpenSearchMigrations\Migrator;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * Roll back and re-run all OpenSearch migrations.
 */
class RefreshCommand extends Command
{
    use ConfirmableTrait;

    /**
     * @var string
     */
    protected $signature = 'opensearch:migrate:refresh 
        {--force : Force the operation to run when in production}';

    /**
     * @var string
     */
    protected $description = 'Reset and re-run all migrations';

    /**
     * Execute the console command.
     */
    public function handle(Migrator $migrator): int
    {
        $migrator->setOutput($this->output);

        if (! $this->confirmToProceed() || ! $migrator->isReady()) {
            return 1;
        }

        $migrator->rollbackAll();
        $migrator->migrateAll();

        return 0;
    }
}
