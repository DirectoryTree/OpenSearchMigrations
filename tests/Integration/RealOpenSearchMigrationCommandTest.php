<?php

use DirectoryTree\OpenSearchAdapter\Indices\Mapping;
use DirectoryTree\OpenSearchAdapter\Indices\Settings;
use DirectoryTree\OpenSearchMigrations\IndexManagerInterface;
use DirectoryTree\OpenSearchMigrations\Tests\Integration\RealOpenSearchTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use OpenSearch\Client;

uses(RealOpenSearchTestCase::class, RefreshDatabase::class);

it('runs migration commands against opensearch', function (): void {
    $prefix = config('opensearch-migrations.index_name_prefix');
    $client = app(Client::class);
    $index = $prefix.'real_test';
    $aliasName = $prefix.'real_alias';

    expect(Artisan::call('opensearch:migrate', ['--force' => true]))->toBe(0);
    expect($client->indices()->exists(['index' => $index]))->toBeTrue();

    $mapping = $client->indices()->getMapping(['index' => $index]);

    expect($mapping[$index]['mappings']['properties']['title']['type'] ?? null)->toBe('text');

    $aliases = $client->indices()->getAlias(['index' => $index]);

    expect($aliases[$index]['aliases'])->toHaveKey($aliasName);

    expect(Artisan::call('opensearch:migrate:status'))->toBe(0);
    expect(Artisan::call('opensearch:migrate:rollback', [
        'fileName' => '2026_01_01_000001_add_real_test_alias',
        '--force' => true,
    ]))->toBe(0);

    expect(DB::table('real_opensearch_migrations')->where('migration', '2026_01_01_000001_add_real_test_alias')->exists())->toBeFalse();

    expect(Artisan::call('opensearch:migrate:reset', ['--force' => true]))->toBe(0);
    expect($client->indices()->exists(['index' => $index]))->toBeFalse();
});

it('runs index adapter operations against opensearch', function (): void {
    $indices = app(IndexManagerInterface::class);
    $client = app(Client::class);
    $prefix = config('opensearch-migrations.index_name_prefix');

    $index = $prefix.'adapter_real_test';
    $aliasName = $prefix.'adapter_real_alias';

    try {
        $indices->create('adapter_real_test', function (Mapping $mapping, Settings $settings): void {
            $mapping->text('title')->keyword('status');

            $settings->index([
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ]);
        });

        expect($client->indices()->exists(['index' => $index]))->toBeTrue();

        $indices->putMapping('adapter_real_test', function (Mapping $mapping): void {
            $mapping->keyword('category');
        });

        $mapping = $client->indices()->getMapping(['index' => $index]);

        expect($mapping[$index]['mappings']['properties']['category']['type'] ?? null)->toBe('keyword');

        $indices->putAlias('adapter_real_test', 'adapter_real_alias', [
            'term' => ['status' => 'published'],
        ]);

        $aliases = $client->indices()->getAlias(['index' => $index]);

        expect($aliases[$index]['aliases'][$aliasName]['filter'] ?? null)->toBe([
            'term' => ['status' => 'published'],
        ]);

        $indices->deleteAlias('adapter_real_test', 'adapter_real_alias');

        $aliases = $client->indices()->getAlias(['index' => $index]);

        expect($aliases[$index]['aliases'])->not->toHaveKey($aliasName);

        $indices->dropIfExists('adapter_real_test');

        expect($client->indices()->exists(['index' => $index]))->toBeFalse();
    } finally {
        if ($client->indices()->exists(['index' => $index])) {
            $client->indices()->delete(['index' => $index]);
        }
    }
});
