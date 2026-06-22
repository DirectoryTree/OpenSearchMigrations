<?php

use DirectoryTree\OpenSearchMigrations\Tests\Integration\TestCase as IntegrationTestCase;
use PHPUnit\Framework\TestCase as UnitTestCase;

uses(IntegrationTestCase::class)->in(
    'Integration/Console',
    'Integration/Factories',
    'Integration/Filesystem',
    'Integration/Repositories',
    'Integration/Support',
    'Integration/MigratorTest.php',
);
uses(IntegrationTestCase::class)->in(
    'Unit/Adapters',
    'Unit/Facades',
);
uses(UnitTestCase::class)->in('Unit/Filesystem');
