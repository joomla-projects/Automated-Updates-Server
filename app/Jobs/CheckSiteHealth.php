<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Site;
use App\Services\SiteConnectionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckSiteHealth implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected readonly Site $site)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var SiteConnectionService $connection */
        $connection = $this->site->connection;

        $response = $connection->checkHealth();

        $healthData = collect($response);

        // Write updated data to DB
        $this->site->update(
            $healthData->only([
                'php_version',
                'db_type',
                'db_version',
                'cms_version',
                'server_os'
            ])->toArray()
        );
    }
}
