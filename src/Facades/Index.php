<?php

namespace DirectoryTree\OpenSearchMigrations\Facades;

use DirectoryTree\OpenSearchMigrations\IndexManagerInterface;
use Illuminate\Support\Facades\Facade;

/**
 * Access the OpenSearch migration index manager.
 *
 * @method static self create(string $index, ?callable $modifier = null)
 * @method static self createIfNotExists(string $index, ?callable $modifier = null)
 * @method static self putMapping(string $index, callable $modifier)
 * @method static self putSettings(string $index, callable $modifier)
 * @method static self pushSettings(string $index, callable $modifier)
 * @method static self drop(string $index)
 * @method static self dropIfExists(string $index)
 * @method static self putAlias(string $index, string $aliasName, array $filter = null)
 * @method static self deleteAlias(string $index, string $aliasName)
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
