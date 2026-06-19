<?php

namespace DirectoryTree\OpenSearchMigrations\Tests\Integration\Console;

use DirectoryTree\OpenSearchMigrations\Console\MakeCommand;
use DirectoryTree\OpenSearchMigrations\Filesystem\MigrationStorage;
use DirectoryTree\OpenSearchMigrations\Tests\Integration\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class MakeCommandTest extends TestCase
{
    public function test_migration_file_can_be_created(): void
    {
        $migrationStorageMock = $this->createMock(MigrationStorage::class);
        $this->app->instance(MigrationStorage::class, $migrationStorageMock);

        /** @var string $migrationStub */
        $migrationStub = file_get_contents(dirname(__DIR__, 3).'/src/Console/stubs/migration.blank.stub');

        $migrationStorageMock
            ->expects($this->once())
            ->method('create')
            ->with(
                $this->stringEndsWith('_test_migration_creation'),
                str_replace('DummyClass', 'TestMigrationCreation', $migrationStub)
            );

        $command = new MakeCommand;
        $command->setLaravel($this->app);

        $input = new ArrayInput(['name' => 'test_migration_creation']);
        $output = new BufferedOutput;

        $resultCode = $command->run($input, $output);
        $resultMessage = $output->fetch();

        $this->assertSame(0, $resultCode);

        $this->assertStringContainsString('Created migration', $resultMessage);
        $this->assertStringContainsString('_test_migration_creation', $resultMessage);
    }
}
