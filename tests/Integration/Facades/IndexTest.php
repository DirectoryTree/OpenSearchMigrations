<?php

namespace DirectoryTree\OpenSearchMigrations\Tests\Integration\Facades;

use DirectoryTree\OpenSearchMigrations\Facades\Index;
use DirectoryTree\OpenSearchMigrations\IndexManagerInterface;
use DirectoryTree\OpenSearchMigrations\Tests\Integration\TestCase;

class IndexTest extends TestCase
{
    public function test_facade_instantiates_instance_of_correct_type(): void
    {
        $this->assertInstanceOf(IndexManagerInterface::class, Index::getFacadeRoot());
    }
}
