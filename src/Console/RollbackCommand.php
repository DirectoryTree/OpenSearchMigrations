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
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opensearch:migrate:rollback 
        {fileName? : The name of the migration file}
        {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback migrations';

    /**
     * Execute the console command.
     */
    public function handle(Migrator $migrator): int
    {
        $migrator->setOutput($this->output);

        if (! $this->confirmToProceed()) {
            return 1;
        }

        $migrator->prepare();

        if ($fileName = $this->argument('fileName')) {
            $migrator->rollbackOne($fileName);
        } else {
            $migrator->rollbackLastBatch();
        }

        return 0;
    }
}
