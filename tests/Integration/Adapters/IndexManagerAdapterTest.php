<?php

namespace DirectoryTree\OpenSearchMigrations\Tests\Integration\Adapters;

use DirectoryTree\OpenSearchAdapter\Indices\Alias;
use DirectoryTree\OpenSearchAdapter\Indices\IndexBlueprint;
use DirectoryTree\OpenSearchAdapter\Indices\IndexManager;
use DirectoryTree\OpenSearchAdapter\Indices\Mapping;
use DirectoryTree\OpenSearchAdapter\Indices\Settings;
use DirectoryTree\OpenSearchMigrations\Adapters\IndexManagerAdapter;
use DirectoryTree\OpenSearchMigrations\Tests\Integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

class IndexManagerAdapterTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $indexManagerMock;

    /**
     * @var IndexManagerAdapter
     */
    protected $indexManagerAdapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->indexManagerMock = $this->createMock(IndexManager::class);
        $this->indexManagerAdapter = new IndexManagerAdapter($this->indexManagerMock);
    }

    #[DataProvider('prefixProvider')]
    public function test_index_can_be_created_without_modifier(string $indexPrefix): void
    {
        $this->app['config']->set('opensearch-migrations.index_name_prefix', $indexPrefix);

        $index = 'test';

        $this->indexManagerMock
            ->expects($this->once())
            ->method('create')
            ->with(new IndexBlueprint($indexPrefix.$index));

        $this->indexManagerAdapter->create($index);
    }

    #[DataProvider('prefixProvider')]
    public function test_index_can_be_created_with_modifier(string $indexPrefix): void
    {
        $this->app['config']->set('opensearch-migrations.index_name_prefix', $indexPrefix);

        $index = 'test';

        $modifier = function (Mapping $mapping, Settings $settings) {
            $mapping->text('title');
            $settings->index(['number_of_replicas' => 2]);
        };

        $this->indexManagerMock
            ->expects($this->once())
            ->method('create')
            ->with(new IndexBlueprint(
                $indexPrefix.$index,
                (new Mapping)->text('title'),
                (new Settings)->index(['number_of_replicas' => 2])
            ));

        $this->indexManagerAdapter->create($index, $modifier);
    }

    #[DataProvider('prefixProvider')]
    public function test_index_with_modifier_can_be_created_only_if_it_does_not_exist(string $indexPrefix): void
    {
        $this->app['config']->set('opensearch-migrations.index_name_prefix', $indexPrefix);

        $index = 'test';

        $this->indexManagerMock
            ->expects($this->once())
            ->method('exists')
            ->with($indexPrefix.$index)
            ->willReturn(false);

        $this->indexManagerMock
            ->expects($this->once())
            ->method('create')
            ->with(new IndexBlueprint($indexPrefix.$index));

        $this->indexManagerAdapter->createIfNotExists($index);
    }

    #[DataProvider('prefixProvider')]
    public function test_mapping_can_be_updated_using_modifier(string $indexPrefix): void
    {
        $this->app['config']->set('opensearch-migrations.index_name_prefix', $indexPrefix);

        $index = 'test';

        $modifier = function (Mapping $mapping) {
            $mapping->disableSource()->text('title');
        };

        $this->indexManagerMock
            ->expects($this->once())
            ->method('putMapping')
            ->with(
                $indexPrefix.$index,
                (new Mapping)->disableSource()->text('title')
            );

        $this->indexManagerAdapter->putMapping($index, $modifier);
    }

    #[DataProvider('prefixProvider')]
    public function test_settings_can_be_updated_using_modifier(string $indexPrefix): void
    {
        $this->app['config']->set('opensearch-migrations.index_name_prefix', $indexPrefix);

        $index = 'test';

        $modifier = function (Settings $settings) {
            $settings->index(['number_of_replicas' => 2, 'refresh_interval' => -1]);
        };

        $this->indexManagerMock
            ->expects($this->once())
            ->method('putSettings')
            ->with(
                $indexPrefix.$index,
                (new Settings)->index(['number_of_replicas' => 2, 'refresh_interval' => -1])
            );

        $this->indexManagerAdapter->putSettings($index, $modifier);
    }

    #[DataProvider('prefixProvider')]
    public function test_settings_can_be_pushed_using_modifier(string $indexPrefix): void
    {
        $this->app['config']->set('opensearch-migrations.index_name_prefix', $indexPrefix);

        $index = 'test';

        $modifier = function (Settings $settings) {
            $settings->index(['number_of_replicas' => 2]);
        };

        $this->indexManagerMock
            ->expects($this->once())
            ->method('close')
            ->with($indexPrefix.$index);

        $this->indexManagerMock
            ->expects($this->once())
            ->method('putSettings')
            ->with(
                $indexPrefix.$index,
                (new Settings)->index(['number_of_replicas' => 2])
            );

        $this->indexManagerMock
            ->expects($this->once())
            ->method('open')
            ->with($indexPrefix.$index);

        $this->indexManagerAdapter->pushSettings($index, $modifier);
    }

    #[DataProvider('prefixProvider')]
    public function test_index_can_be_dropped(string $indexPrefix): void
    {
        $this->app['config']->set('opensearch-migrations.index_name_prefix', $indexPrefix);

        $index = 'test';

        $this->indexManagerMock
            ->expects($this->once())
            ->method('delete')
            ->with($indexPrefix.$index);

        $this->indexManagerAdapter->drop($index);
    }

    #[DataProvider('prefixProvider')]
    public function test_index_can_be_dropped_only_if_exists(string $indexPrefix): void
    {
        $this->app['config']->set('opensearch-migrations.index_name_prefix', $indexPrefix);

        $index = 'test';

        $this->indexManagerMock
            ->expects($this->once())
            ->method('exists')
            ->with($indexPrefix.$index)
            ->willReturn(true);

        $this->indexManagerMock
            ->expects($this->once())
            ->method('delete')
            ->with($indexPrefix.$index);

        $this->indexManagerAdapter->dropIfExists($index);
    }

    #[DataProvider('prefixProvider')]
    public function test_alias_can_be_created(string $aliasNamePrefix): void
    {
        $this->app['config']->set('opensearch-migrations.index_name_prefix', $aliasNamePrefix);
        $this->app['config']->set('opensearch-migrations.alias_name_prefix', $aliasNamePrefix);

        $index = 'foo';
        $aliasName = 'bar';

        $this->indexManagerMock
            ->expects($this->once())
            ->method('putAlias')
            ->with($aliasNamePrefix.$index, new Alias($aliasNamePrefix.$aliasName));

        $this->indexManagerAdapter->putAlias($index, $aliasName);
    }

    #[DataProvider('prefixProvider')]
    public function test_alias_can_be_deleted(string $aliasNamePrefix): void
    {
        $this->app['config']->set('opensearch-migrations.index_name_prefix', $aliasNamePrefix);
        $this->app['config']->set('opensearch-migrations.alias_name_prefix', $aliasNamePrefix);

        $index = 'foo';
        $aliasName = 'bar';

        $this->indexManagerMock
            ->expects($this->once())
            ->method('deleteAlias')
            ->with($aliasNamePrefix.$index, $aliasNamePrefix.$aliasName);

        $this->indexManagerAdapter->deleteAlias($index, $aliasName);
    }

    public static function prefixProvider(): array
    {
        return [
            'no prefix' => [''],
            'short prefix' => ['foo_'],
            'long prefix' => ['foo_bar_'],
        ];
    }
}
