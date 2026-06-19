<?php

namespace DirectoryTree\OpenSearchMigrations\Console;

use DirectoryTree\OpenSearchMigrations\Migrator;
use Illuminate\Console\Command;

/**
 * Display the OpenSearch migration status.
 */
class StatusCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'opensearch:migrate:status';

    /**
     * @var string
     */
    protected $description = 'Show the status of each migration';

    /**
     * Execute the console command.
     */
    public function handle(Migrator $migrator): int
    {
        $migrator->setOutput($this->output);

        if (! $migrator->isReady()) {
            return 1;
        }

        $migrator->showStatus();

        return 0;
    }
}
