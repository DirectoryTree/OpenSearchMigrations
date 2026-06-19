<?php

namespace DirectoryTree\OpenSearchMigrations;

interface ReadinessInterface
{
    public function isReady(): bool;
}
