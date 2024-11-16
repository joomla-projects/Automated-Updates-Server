<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Site;
use app\Remotesite\Connection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateSite implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected readonly Site $site, protected string $targetVersion)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var Connection $connection */
        $connection = $this->site->connection;

        // Test connection and get current version
        $healthResult = $connection->checkHealth();
    }
}
