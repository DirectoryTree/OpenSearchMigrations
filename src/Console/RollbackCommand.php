<?php

namespace DirectoryTree\OpenSearchMigrations\Console;

use DirectoryTree\OpenSearchMigrations\Migrator;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * Roll back OpenSearch migrations.
 */
class RollbackCommand extends Command
{
    use ConfirmableTrait;

    /**
     * @var string
     */
    protected $signature = 'opensearch:migrate:rollback 
        {fileName? : The name of the migration file}
        {--force : Force the operation to run when in production}';

    /**
     * @var string
     */
    protected $description = 'Rollback migrations';

    /**
     * Execute the console command.
     */
    public function handle(Migrator $migrator): int
    {
        $migrator->setOutput($this->output);

        if (! $this->confirmToProceed() || ! $migrator->isReady()) {
            return 1;
        }

        if ($fileName = $this->argument('fileName')) {
            $migrator->rollbackOne($fileName);
        } else {
            $migrator->rollbackLastBatch();
        }

        return 0;
    }
}
