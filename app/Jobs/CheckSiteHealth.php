<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Site;
use App\RemoteSite\Connection;
use App\TUF\TufFetcher;
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

        // Check if a newer Joomla version for that site is available
        $latestVersion = (new TufFetcher())->getLatestVersionForBranch((int) $this->site->cms_version[0]);

        // Available version is not newer, exit
        if (!version_compare($latestVersion, $this->site->cms_version, ">")) {
            return;
        }

        // We have a newer version, queue Update
        UpdateSite::dispatch(
            $this->site,
            $latestVersion
        );
    }
}
