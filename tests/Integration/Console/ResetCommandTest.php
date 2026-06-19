<?php

namespace DirectoryTree\OpenSearchMigrations\Tests\Integration\Console;

use DirectoryTree\OpenSearchMigrations\Console\ResetCommand;
use DirectoryTree\OpenSearchMigrations\Migrator;
use DirectoryTree\OpenSearchMigrations\Tests\Integration\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ResetCommandTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $migrator;

    /**
     * @var ResetCommand
     */
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrator = $this->createMock(Migrator::class);
        $this->app->instance(Migrator::class, $this->migrator);

        $this->command = new ResetCommand;
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
            ->method('rollbackAll');

        $result = $this->command->run(
            new ArrayInput(['--force' => true]),
            new NullOutput
        );

        $this->assertSame(1, $result);
    }

    public function test_rollbacks_all_migrations_if_migrator_is_ready(): void
    {
        $this->migrator
            ->expects($this->once())
            ->method('isReady')
            ->willReturn(true);

        $this->migrator
            ->expects($this->once())
            ->method('rollbackAll');

        $result = $this->command->run(
            new ArrayInput(['--force' => true]),
            new NullOutput
        );

        $this->assertSame(0, $result);
    }
}
