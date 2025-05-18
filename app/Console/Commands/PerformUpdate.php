<?php

namespace App\Console\Commands;

use App\Jobs\UpdateSite;
use App\Models\Site;
use Illuminate\Console\Command;
use App\Console\Traits\RequestTargetVersion;

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

        if (!$site->update_requirement_state) {
            $this->output->writeln('Update requirements not met, aborting!');

            return Command::FAILURE;
        }

        UpdateSite::dispatchSync(
            $site,
            $targetVersion
        );

        return Command::SUCCESS;
    }
}
