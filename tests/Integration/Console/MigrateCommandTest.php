<?php

namespace DirectoryTree\OpenSearchMigrations\Tests\Integration\Console;

use DirectoryTree\OpenSearchMigrations\Console\MigrateCommand;
use DirectoryTree\OpenSearchMigrations\Migrator;
use DirectoryTree\OpenSearchMigrations\Tests\Integration\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class MigrateCommandTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $migrator;

    /**
     * @var MigrateCommand
     */
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrator = $this->createMock(Migrator::class);
        $this->app->instance(Migrator::class, $this->migrator);

        $this->command = new MigrateCommand;
        $this->command->setLaravel($this->app);
    }

    public function test_does_nothing_if_migrator_is_not_ready(): void
    {
        $this->migrator
            ->expects($this->once())
            ->method('isReady')
            ->willReturn(false);

        $this->migrator
            ->expects($this->never())
            ->method('migrateOne');

        $this->migrator
            ->expects($this->never())
            ->method('migrateAll');

        $result = $this->command->run(
            new ArrayInput(['--force' => true]),
            new NullOutput
        );

        $this->assertSame(1, $result);
    }

    public function test_runs_one_migration_if_file_name_is_provided(): void
    {
        $this->migrator
            ->expects($this->once())
            ->method('isReady')
            ->willReturn(true);

        $this->migrator
            ->expects($this->once())
            ->method('migrateOne')
            ->with('test_file_name');

        $result = $this->command->run(
            new ArrayInput(['--force' => true, 'fileName' => 'test_file_name']),
            new NullOutput
        );

        $this->assertSame(0, $result);
    }

    public function test_runs_all_migrations_if_file_name_is_not_provided(): void
    {
        $this->migrator
            ->expects($this->once())
            ->method('isReady')
            ->willReturn(true);

        $this->migrator
            ->expects($this->once())
            ->method('migrateAll');

        $result = $this->command->run(
            new ArrayInput(['--force' => true]),
            new NullOutput
        );

        $this->assertSame(0, $result);
    }
}
