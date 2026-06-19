<?php

namespace DirectoryTree\OpenSearchMigrations\Tests\Integration\Factories;

use DirectoryTree\OpenSearchMigrations\Factories\MigrationFactory;
use DirectoryTree\OpenSearchMigrations\Filesystem\MigrationStorage;
use DirectoryTree\OpenSearchMigrations\MigrationInterface;
use DirectoryTree\OpenSearchMigrations\Tests\Integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MigrationFactoryTest extends TestCase
{
    /**
     * @var MigrationFactory
     */
    protected $migrationFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrationFactory = resolve(MigrationFactory::class);
    }

    public static function fileNameProvider(): array
    {
        return [
            ['2018_12_01_081000_create_test_index'],
            ['2019_08_10_142230_update_test_index_mapping'],
        ];
    }

    #[DataProvider('fileNameProvider')]
    public function test_migration_can_be_created_from_file(string $fileName): void
    {
        $file = resolve(MigrationStorage::class)->findByName($fileName);

        $this->assertInstanceOf(
            MigrationInterface::class,
            $this->migrationFactory->makeFromFile($file)
        );
    }
}
