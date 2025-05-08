<?php

namespace App\Console\Commands;

use App\Jobs\UpdateSite;
use App\Models\Site;
use Illuminate\Console\Command;
use App\Console\Traits\RequestTargetVersion;
use Illuminate\Support\Facades\Log;

class PerformUpdate extends Command
{
    use RequestTargetVersion;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:perform-update {siteId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executes an update job for given site id';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetVersion = $this->queryTargetVersion();

        /** @var Site $site */
        $site = Site::findOrFail($this->input->getArgument('siteId'));

        $updateCount = $site->updates()->where('new_version', $targetVersion)->count();

        if ($updateCount >= config('autoupdates.max_update_tries')) {
            Log::info("Update Loop detected for Site: " . $site->id . '; TargetVersion: ' . $targetVersion);

            return Command::SUCCESS;
        }

        UpdateSite::dispatchSync(
            $site,
            $targetVersion
        );

        return Command::SUCCESS;
    }
}
