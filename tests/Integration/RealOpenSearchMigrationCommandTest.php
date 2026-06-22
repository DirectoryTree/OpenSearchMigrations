<?php

namespace DirectoryTree\OpenSearchMigrations\Tests\Integration;

use DirectoryTree\OpenSearchAdapter\Indices\Mapping;
use DirectoryTree\OpenSearchAdapter\Indices\Settings;
use DirectoryTree\OpenSearchMigrations\IndexManagerInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RealOpenSearchMigrationCommandTest extends RealOpenSearchTestCase
{
    use RefreshDatabase;

    public function test_migration_commands_run_against_opensearch(): void
    {
        $index = $this->indexPrefix.'real_test';
        $aliasName = $this->indexPrefix.'real_alias';

        $this->artisan('opensearch:migrate', ['--force' => true])
            ->assertExitCode(0);

        $this->assertTrue($this->client()->indices()->exists(['index' => $index]));

        $mapping = $this->client()->indices()->getMapping(['index' => $index]);

        $this->assertSame(
            'text',
            $mapping[$index]['mappings']['properties']['title']['type'] ?? null
        );

        $aliases = $this->client()->indices()->getAlias(['index' => $index]);

        $this->assertArrayHasKey($aliasName, $aliases[$index]['aliases']);

        $this->artisan('opensearch:migrate:status')
            ->assertExitCode(0);

        $this->artisan('opensearch:migrate:rollback', [
            'fileName' => '2026_01_01_000001_add_real_test_alias',
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('real_opensearch_migrations', [
            'migration' => '2026_01_01_000001_add_real_test_alias',
        ]);

        $this->artisan('opensearch:migrate:reset', ['--force' => true])
            ->assertExitCode(0);

        $this->assertFalse($this->client()->indices()->exists(['index' => $index]));
    }

    public function test_index_adapter_operations_run_against_opensearch(): void
    {
        $indices = app(IndexManagerInterface::class);

        $index = $this->indexPrefix.'adapter_real_test';
        $aliasName = $this->indexPrefix.'adapter_real_alias';

        try {
            $indices->create('adapter_real_test', function (Mapping $mapping, Settings $settings): void {
                $mapping
                    ->text('title')
                    ->keyword('status');

                $settings->index([
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                ]);
            });

            $this->assertTrue($this->client()->indices()->exists(['index' => $index]));

            $indices->putMapping('adapter_real_test', function (Mapping $mapping): void {
                $mapping->keyword('category');
            });

            $mapping = $this->client()->indices()->getMapping(['index' => $index]);

            $this->assertSame(
                'keyword',
                $mapping[$index]['mappings']['properties']['category']['type'] ?? null
            );

            $indices->putAlias('adapter_real_test', 'adapter_real_alias', [
                'term' => [
                    'status' => 'published',
                ],
            ]);

            $aliases = $this->client()->indices()->getAlias(['index' => $index]);

            $this->assertSame(
                ['term' => ['status' => 'published']],
                $aliases[$index]['aliases'][$aliasName]['filter'] ?? null
            );

            $indices->deleteAlias('adapter_real_test', 'adapter_real_alias');

            $aliases = $this->client()->indices()->getAlias(['index' => $index]);

            $this->assertArrayNotHasKey($aliasName, $aliases[$index]['aliases']);

            $indices->dropIfExists('adapter_real_test');

            $this->assertFalse($this->client()->indices()->exists(['index' => $index]));
        } finally {
            $this->dropIndexIfExists($index);
        }
    }
}
