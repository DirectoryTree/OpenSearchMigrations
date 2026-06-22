<?php

use DirectoryTree\OpenSearchAdapter\Indices\Mapping;
use DirectoryTree\OpenSearchMigrations\Facades\Index;
use DirectoryTree\OpenSearchMigrations\MigrationInterface;

class CreateRealTestIndex implements MigrationInterface
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Index::create('real_test', function (Mapping $mapping) {
            $mapping->text('title');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Index::drop('real_test');
    }
}
