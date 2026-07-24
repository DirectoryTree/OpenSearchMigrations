<?php

use DirectoryTree\OpenSearchMigrations\Support\MigrationPrefix;

it('prefixes index names', function () {
    config()->set('opensearch-migrations.index_name_prefix', 'tenant_');

    expect(MigrationPrefix::index('posts'))->toBe('tenant_posts');
});

it('prefixes alias names', function () {
    config()->set('opensearch-migrations.alias_name_prefix', 'tenant_');

    expect(MigrationPrefix::alias('posts_read'))->toBe('tenant_posts_read');
});
