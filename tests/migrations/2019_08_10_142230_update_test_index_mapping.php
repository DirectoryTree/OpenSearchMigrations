<?php

use DirectoryTree\OpenSearchAdapter\Indices\Mapping;
use DirectoryTree\OpenSearchMigrations\Facades\Index;
use DirectoryTree\OpenSearchMigrations\MigrationInterface;

class UpdateTestIndexMapping implements MigrationInterface
{
    public function up(): void
    {
        Index::putMapping('test', static function (Mapping $mapping) {
            $mapping->enableSource();
            $mapping->text('title');
        });
    }

    public function down(): void
    {
        Index::putMapping('test', static function (Mapping $mapping) {
            $mapping->disableSource();
        });
    }
}
