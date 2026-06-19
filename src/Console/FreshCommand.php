<?php

namespace DirectoryTree\OpenSearchMigrations\Console;

use DirectoryTree\OpenSearchMigrations\IndexManagerInterface;
use DirectoryTree\OpenSearchMigrations\Migrator;
use DirectoryTree\OpenSearchMigrations\Repositories\MigrationRepository;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * Drop all indices and re-run all OpenSearch migrations.
 */
class FreshCommand extends Command
{
    use ConfirmableTrait;

    /**
     * @var string
     */
    protected $signature = 'opensearch:migrate:fresh 
        {--force : Force the operation to run when in production}';

    /**
     * @var string
     */
    protected $description = 'Drop all indices and re-run all migrations';

    /**
     * Execute the console command.
     */
    public function handle(
        Migrator $migrator,
        MigrationRepository $migrationRepository,
        IndexManagerInterface $indexManager
    ): int {
        $migrator->setOutput($this->output);

        if (! $this->confirmToProceed() || ! $migrator->isReady()) {
            return 1;
        }

        $indexManager->drop('*');

        $migrationRepository->deleteAll();

        $migrator->migrateAll();

        return 0;
    }
}
