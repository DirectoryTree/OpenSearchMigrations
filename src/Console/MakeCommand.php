<?php

namespace DirectoryTree\OpenSearchMigrations\Console;

use Carbon\Carbon;
use DirectoryTree\OpenSearchMigrations\Filesystem\MigrationStorage;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Create OpenSearch migration files.
 */
class MakeCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'opensearch:make:migration 
        {name : The name of the migration}';

    /**
     * @var string
     */
    protected $description = 'Create a new migration file';

    /**
     * Execute the console command.
     */
    public function handle(Filesystem $filesystem, MigrationStorage $migrationStorage): int
    {
        $name = Str::snake(trim($this->argument('name')));

        $fileName = sprintf('%s_%s', (new Carbon)->format('Y_m_d_His'), $name);
        $className = Str::studly($name);

        $stub = $filesystem->get(__DIR__.'/stubs/migration.blank.stub');
        $content = str_replace('DummyClass', $className, $stub);

        $migrationStorage->create($fileName, $content);

        $this->output->writeln('<info>Created migration:</info> '.$fileName);

        return 0;
    }
}
