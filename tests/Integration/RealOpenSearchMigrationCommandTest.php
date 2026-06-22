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
        $indexName = $this->indexPrefix.'real_test';
        $aliasName = $this->indexPrefix.'real_alias';

        $this->artisan('opensearch:migrate', ['--force' => true])
            ->assertExitCode(0);

        $this->assertTrue($this->client()->indices()->exists(['index' => $indexName]));

        $mapping = $this->client()->indices()->getMapping(['index' => $indexName]);

        $this->assertSame(
            'text',
            $mapping[$indexName]['mappings']['properties']['title']['type'] ?? null
        );

        $aliases = $this->client()->indices()->getAlias(['index' => $indexName]);

        $this->assertArrayHasKey($aliasName, $aliases[$indexName]['aliases']);

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

        $this->assertFalse($this->client()->indices()->exists(['index' => $indexName]));
    }

    public function test_index_adapter_operations_run_against_opensearch(): void
    {
        $index = app(IndexManagerInterface::class);

        $indexName = $this->indexPrefix.'adapter_real_test';
        $aliasName = $this->indexPrefix.'adapter_real_alias';

        try {
            $index->create('adapter_real_test', function (Mapping $mapping, Settings $settings): void {
                $mapping
                    ->text('title')
                    ->keyword('status');

                $settings->index([
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                ]);
            });

            $this->assertTrue($this->client()->indices()->exists(['index' => $indexName]));

            $index->putMapping('adapter_real_test', function (Mapping $mapping): void {
                $mapping->keyword('category');
            });

            $mapping = $this->client()->indices()->getMapping(['index' => $indexName]);

            $this->assertSame(
                'keyword',
                $mapping[$indexName]['mappings']['properties']['category']['type'] ?? null
            );

            $index->putAlias('adapter_real_test', 'adapter_real_alias', [
                'term' => [
                    'status' => 'published',
                ],
            ]);

            $aliases = $this->client()->indices()->getAlias(['index' => $indexName]);

            $this->assertSame(
                ['term' => ['status' => 'published']],
                $aliases[$indexName]['aliases'][$aliasName]['filter'] ?? null
            );

            $index->deleteAlias('adapter_real_test', 'adapter_real_alias');

            $aliases = $this->client()->indices()->getAlias(['index' => $indexName]);

            $this->assertArrayNotHasKey($aliasName, $aliases[$indexName]['aliases']);

            $index->dropIfExists('adapter_real_test');

            $this->assertFalse($this->client()->indices()->exists(['index' => $indexName]));
        } finally {
            $this->dropIndexIfExists($indexName);
        }
    }
}
