<?php

namespace DirectoryTree\OpenSearchMigrations\Tests\Integration\Support;

use DirectoryTree\OpenSearchMigrations\Support\Prefix;
use DirectoryTree\OpenSearchMigrations\Tests\Integration\TestCase;

class PrefixTest extends TestCase
{
    public function test_index_names_can_be_prefixed(): void
    {
        $this->app['config']->set('opensearch-migrations.index_name_prefix', 'tenant_');

        $this->assertSame('tenant_posts', Prefix::index('posts'));
    }

    public function test_alias_names_can_be_prefixed(): void
    {
        $this->app['config']->set('opensearch-migrations.alias_name_prefix', 'tenant_');

        $this->assertSame('tenant_posts_read', Prefix::alias('posts_read'));
    }
}
