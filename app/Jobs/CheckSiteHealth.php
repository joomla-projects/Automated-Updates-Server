<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Site;
use App\RemoteSite\Connection;
use Carbon\Carbon;
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
        /** @var Connection $connection */
        $connection = $this->site->connection;

        $healthData = $connection->checkHealth();

        // Write updated data to DB
        $this->site->fill(
            $healthData->toArray()
        );

        // @phpstan-ignore-next-line
        $this->site->last_seen = Carbon::now();
        $this->site->save();
    }
}
