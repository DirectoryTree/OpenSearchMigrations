<?php

namespace DirectoryTree\OpenSearchMigrations;

interface MigrationInterface
{
    public function up(): void;

    public function down(): void;
}
