<?php

namespace DirectoryTree\OpenSearchMigrations\Console;

use DirectoryTree\OpenSearchMigrations\Deployments\Deployment;
use DirectoryTree\OpenSearchMigrations\Repositories\DeploymentRepository;
use Illuminate\Console\Command;

/**
 * Display OpenSearch deployment status.
 */
class DeploymentStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opensearch:deploy:status
        {name? : The logical index name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of index deployments';

    /**
     * Execute the console command.
     */
    public function handle(DeploymentRepository $deployments): int
    {
        $deployments->prepare();

        $records = $this->argument('name')
            ? collect([$deployments->findOrFail($this->argument('name'))])
            : $deployments->all();

        $this->table(
            ['Name', 'Status', 'Alias', 'Active', 'Candidate', 'Previous'],
            $records->map(fn (Deployment $deployment) => [
                $deployment->name,
                $deployment->status->value,
                $deployment->alias,
                $deployment->activeIndex,
                $deployment->candidateIndex ?? '-',
                $deployment->previousIndex ?? '-',
            ])->all()
        );

        return static::SUCCESS;
    }
}
