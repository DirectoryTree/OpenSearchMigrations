<?php

namespace DirectoryTree\OpenSearchMigrations\Facades;

use DirectoryTree\OpenSearchMigrations\IndexManagerInterface;
use Illuminate\Support\Facades\Facade;

/**
 * Access the OpenSearch migration index manager.
 *
 * @method static IndexManagerInterface create(string $index, ?callable $modifier = null)
 * @method static IndexManagerInterface createIfNotExists(string $index, ?callable $modifier = null)
 * @method static IndexManagerInterface putMapping(string $index, callable $modifier)
 * @method static IndexManagerInterface putSettings(string $index, callable $modifier)
 * @method static IndexManagerInterface pushSettings(string $index, callable $modifier)
 * @method static IndexManagerInterface drop(string $index)
 * @method static IndexManagerInterface dropIfExists(string $index)
 * @method static IndexManagerInterface putAlias(string $index, string $aliasName, ?array $filter = null)
 * @method static IndexManagerInterface deleteAlias(string $index, string $aliasName)
 */
class Index extends Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor(): string
    {
        return IndexManagerInterface::class;
    }
}
