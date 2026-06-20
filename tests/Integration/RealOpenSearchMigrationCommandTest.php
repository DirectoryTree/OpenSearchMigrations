<?php

namespace DirectoryTree\OpenSearchMigrations\Tests\Integration;

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
}
