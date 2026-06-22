<?php

namespace DirectoryTree\OpenSearchMigrations;

use DirectoryTree\OpenSearchMigrations\Factories\MigrationFactory;
use DirectoryTree\OpenSearchMigrations\Filesystem\MigrationFile;
use DirectoryTree\OpenSearchMigrations\Filesystem\MigrationStorage;
use DirectoryTree\OpenSearchMigrations\Repositories\MigrationRepository;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Collection;

/**
 * Runs and rolls back OpenSearch migration files.
 */
class Migrator implements ReadinessInterface
{
    /**
     * @var OutputStyle
     */
    protected $output;

    /**
     * Create a new migrator instance.
     */
    public function __construct(
        protected MigrationRepository $migrationRepository,
        protected MigrationStorage $migrationStorage,
        protected MigrationFactory $migrationFactory
    ) {}

    /**
     * Set the console output implementation.
     */
    public function setOutput(OutputStyle $output): self
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Run a single migration by file name.
     */
    public function migrateOne(string $fileName): self
    {
        $file = $this->migrationStorage->findByName($fileName);

        if (is_null($file)) {
            $this->output->writeln('<error>Migration is not found:</error> '.$fileName);
        } else {
            $this->migrate(collect([$file]));
        }

        return $this;
    }

    /**
     * Run all outstanding migrations.
     */
    public function migrateAll(): self
    {
        $files = $this->migrationStorage->findAll();
        $migratedFileNames = $this->migrationRepository->getAll();

        $nonMigratedFiles = $files->filter(function (MigrationFile $file) use ($migratedFileNames) {
            return ! $migratedFileNames->contains($file->getName());
        });

        $this->migrate($nonMigratedFiles);

        return $this;
    }

    /**
     * Roll back a single migration by file name.
     */
    public function rollbackOne(string $fileName): self
    {
        $file = $this->migrationStorage->findByName($fileName);

        if (is_null($file)) {
            $this->output->writeln('<error>Migration is not found:</error> '.$fileName);
        } elseif (! $this->migrationRepository->exists($file->getName())) {
            $this->output->writeln('<error>Migration is not yet migrated:</error> '.$file->getName());
        } else {
            $this->rollback(collect([$file->getName()]));
        }

        return $this;
    }

    /**
     * Roll back the last migration batch.
     */
    public function rollbackLastBatch(): self
    {
        $fileNames = $this->migrationRepository->getLastBatch();

        $this->rollback($fileNames);

        return $this;
    }

    /**
     * Roll back all migrations.
     */
    public function rollbackAll(): self
    {
        $fileNames = $this->migrationRepository->getAll();

        $this->rollback($fileNames);

        return $this;
    }

    /**
     * Display the migration status table.
     */
    public function showStatus(): self
    {
        $files = $this->migrationStorage->findAll();

        $migratedFileNames = $this->migrationRepository->getAll();
        $migratedLastBatchFileNames = $this->migrationRepository->getLastBatch();

        $headers = ['Ran?', 'Last batch?', 'Migration'];

        $rows = $files->map(function (MigrationFile $file) use ($migratedFileNames, $migratedLastBatchFileNames) {
            return [
                $migratedFileNames->contains($file->getName()) ? '<info>Yes</info>' : '<comment>No</comment>',
                $migratedLastBatchFileNames->contains($file->getName()) ? '<info>Yes</info>' : '<comment>No</comment>',
                $file->getName(),
            ];
        })->toArray();

        $this->output->table($headers, $rows);

        return $this;
    }

    /**
     * Run the given migration files.
     *
     * @param  Collection<int, MigrationFile>  $files
     */
    protected function migrate(Collection $files): self
    {
        if ($files->isEmpty()) {
            $this->output->writeln('<info>Nothing to migrate</info>');

            return $this;
        }

        $nextBatchNumber = $this->migrationRepository->getLastBatchNumber() + 1;

        $files->each(function (MigrationFile $file) use ($nextBatchNumber) {
            $this->output->writeln('<comment>Migrating:</comment> '.$file->getName());

            $migration = $this->migrationFactory->makeFromFile($file);
            $migration->up();

            $this->migrationRepository->insert($file->getName(), $nextBatchNumber);

            $this->output->writeln('<info>Migrated:</info> '.$file->getName());
        });

        return $this;
    }

    /**
     * Roll back the given migration file names.
     *
     * @param  Collection<int, string>  $fileNames
     */
    protected function rollback(Collection $fileNames): self
    {
        $files = $fileNames->map(function (string $fileName) {
            return $this->migrationStorage->findByName($fileName);
        })->filter();

        if ($fileNames->isEmpty()) {
            $this->output->writeln('<info>Nothing to roll back</info>');

            return $this;
        } elseif ($fileNames->count() != $files->count()) {
            $this->output->writeln(
                '<error>Migration is not found:</error> '.
                implode(',', $fileNames->diff($files->map(function (MigrationFile $file) {
                    return $file->getName();
                }))->toArray())
            );

            return $this;
        }

        $files->each(function (MigrationFile $file) {
            $this->output->writeln('<comment>Rolling back:</comment> '.$file->getName());

            $migration = $this->migrationFactory->makeFromFile($file);
            $migration->down();

            $this->migrationRepository->delete($file->getName());

            $this->output->writeln('<info>Rolled back:</info> '.$file->getName());
        });

        return $this;
    }

    /**
     * Determine if migrations can be run.
     */
    public function isReady(): bool
    {
        if (! $isMigrationRepositoryReady = $this->migrationRepository->isReady()) {
            $this->output->writeln('<error>Migration table is not yet created</error>');
        }

        if (! $isMigrationStorageReady = $this->migrationStorage->isReady()) {
            $this->output->writeln('<error>Migration directory is not yet created</error>');
        }

        return $isMigrationRepositoryReady && $isMigrationStorageReady;
    }
}
