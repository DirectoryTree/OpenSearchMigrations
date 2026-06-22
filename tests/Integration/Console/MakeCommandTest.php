<?php

use DirectoryTree\OpenSearchMigrations\Console\MakeCommand;
use DirectoryTree\OpenSearchMigrations\Filesystem\MigrationStorage;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

it('creates migration files', function (): void {
    $migrations = Mockery::mock(MigrationStorage::class);
    app()->instance(MigrationStorage::class, $migrations);

    $stub = file_get_contents(dirname(__DIR__, 3).'/src/Console/stubs/migration.blank.stub');

    $migrations->shouldReceive('create')
        ->once()
        ->with(Mockery::pattern('/_test_migration_creation$/'), str_replace('DummyClass', 'TestMigrationCreation', $stub));

    $command = new MakeCommand;
    $command->setLaravel(app());

    $output = new BufferedOutput;
    $result = $command->run(new ArrayInput(['name' => 'test_migration_creation']), $output);

    expect($result)->toBe(0);
    expect($output->fetch())->toContain('Created migration')->toContain('_test_migration_creation');
});
