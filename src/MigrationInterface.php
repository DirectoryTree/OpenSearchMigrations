<?php

namespace DirectoryTree\OpenSearchMigrations;

/**
 * Defines an OpenSearch migration.
 */
interface MigrationInterface
{
    /**
     * Run the migration.
     */
    public function up(): void;

    /**
     * Reverse the migration.
     */
    public function down(): void;
}
