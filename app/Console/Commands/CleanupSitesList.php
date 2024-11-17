<?php

namespace App\Console\Commands;

use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class CleanupSitesList extends Command
{
    protected int $totalDeleted = 0;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-sites-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup sites that are too old and haven\'t been seen for a while' ;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->output->writeln('Cleanup sites ');

        Site::query()
            ->whereDate(
                'last_seen',
                '<',
                Carbon::now()->subDays((int) config('autoupdates.cleanup_site_delay')) // @phpstan-ignore-line
            )
            ->chunkById(
                100,
                function (Collection $chunk) {
                    // Show progress
                    $this->output->write('.');

                    $this->totalDeleted += $chunk->count();

                    // Push each site check to queue
                    $chunk->each(fn ($site) => $site->delete());
                }
            );

        // Result
        $this->output->writeln("");
        $this->output->writeln('Deleted ' . $this->totalDeleted . ' Sites');

        return Command::SUCCESS;
    }
}
