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
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opensearch:migrate:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each migration';

    /**
     * Execute the console command.
     */
    public function handle(Migrator $migrator): int
    {
        $migrator->setOutput($this->output);

        $migrator->prepare();
        $migrator->showStatus();

        return 0;
    }
}
