<?php

namespace DirectoryTree\OpenSearchMigrations\Deployments;

enum DeploymentStatus: string
{
    case Active = 'active';
    case Backfilling = 'backfilling';
    case Provisioned = 'provisioned';
    case Ready = 'ready';
}
