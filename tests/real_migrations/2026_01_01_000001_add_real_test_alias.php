<?php

use DirectoryTree\OpenSearchMigrations\Facades\Index;
use DirectoryTree\OpenSearchMigrations\MigrationInterface;

class AddRealTestAlias implements MigrationInterface
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Index::putAlias('real_test', 'real_alias');
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Index::deleteAlias('real_test', 'real_alias');
    }
}
