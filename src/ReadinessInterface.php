<?php

namespace DirectoryTree\OpenSearchMigrations;

/**
 * Defines a service that can report whether it is ready to run.
 */
interface ReadinessInterface
{
    /**
     * Determine if the service is ready.
     */
    public function isReady(): bool;
}
