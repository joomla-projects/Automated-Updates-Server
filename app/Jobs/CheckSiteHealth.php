<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Site;
use App\RemoteSite\Connection;
use App\TUF\TufFetcher;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\App;

class CheckSiteHealth implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $uniqueFor = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(protected readonly Site $site)
    {
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->site->id;
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

        // Check if conditions are met
        if (!$this->site->update_requirement_state) {
            return;
        }

        // Check if a newer Joomla version for that site is available
        /** @var TufFetcher $tufFetcher */
        $tufFetcher = App::make(TufFetcher::class);
        $latestVersion = $tufFetcher->getLatestVersionForBranch((int) $this->site->cms_version[0]);

        // No latest version for branch available, unsupported branch - return
        if (!$latestVersion) {
            return;
        }

        // Available version is not newer, exit
        if (version_compare($latestVersion, $this->site->cms_version, "<")) {
            return;
        }

        // We have a newer version, queue Update
        UpdateSite::dispatch(
            $this->site,
            $latestVersion
        )->onQueue('updates');
    }
}
