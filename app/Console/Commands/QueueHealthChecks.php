<?php

namespace App\Console\Commands;

use App\Jobs\CheckSiteHealth;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class QueueHealthChecks extends Command
{
    protected int $totalPushed = 0;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:queue-health-checks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pushes pending health checks to queue';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->output->writeln('Pushing pending health checks');

        Site::query()
            ->whereDate(
                'last_seen',
                '<',
                Carbon::now()->subHours((int) config('autoupdates.healthcheck_interval')) // @phpstan-ignore-line
            )
            ->chunkById(
                100,
                function (Collection $chunk) {
                    // Show progress
                    $this->output->write('.');

                    $this->totalPushed += $chunk->count();

                    // Push each site check to queue
                    $chunk->each(fn ($site) => CheckSiteHealth::dispatch($site));
                }
            );

        // Result
        $this->output->writeln("");
        $this->output->writeln('Pushed ' . $this->totalPushed . ' pending jobs to queue');

        return Command::SUCCESS;
    }
}
