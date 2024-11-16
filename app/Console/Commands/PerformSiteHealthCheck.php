<?php

namespace App\Console\Commands;

use App\Jobs\CheckSiteHealth;
use App\Models\Site;
use Illuminate\Console\Command;

class PerformSiteHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-site-health {siteId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check site health for given site id';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        CheckSiteHealth::dispatchSync(
            Site::findOrFail($this->input->getArgument('siteId'))
        );

        return Command::SUCCESS;
    }
}
