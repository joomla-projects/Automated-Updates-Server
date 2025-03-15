<?php

namespace App\Console\Commands;

use App\Console\Traits\RequestTargetVersion;
use App\Jobs\UpdateSite;
use App\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class QueueUpdates extends Command
{
    use RequestTargetVersion;
    protected int $totalPushed = 0;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:queue-updates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queues updates for all applicable registered sites';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetVersion = $this->queryTargetVersion();

        $this->confirm("Are you sure you would like to push the updates for " . $targetVersion);

        $this->output->writeln('Pushing update jobs');

        $sites = Site::query()
            ->where(
                'cms_version',
                'like',
                $targetVersion[0] . '%'
            );

        // Query the amount of sites to be updated
        $updateCount = (int) $this->ask('How many updates will be pushed? - Use 0 for "ALL"', "100");

        if ($updateCount > 0) {
            $sites->limit($updateCount);
        }

        // Chunk and push to queue
        $sites->chunkById(
            100,
            function (Collection $chunk) use ($targetVersion) {
                // Show progress
                $this->output->write('.');

                $this->totalPushed += $chunk->count();

                // Push each site check to queue
                $chunk->each(fn ($site) => UpdateSite::dispatch($site, $targetVersion));
            }
        );

        // Result
        $this->output->writeln("");
        $this->output->writeln('Pushed ' . $this->totalPushed . ' pending jobs to queue');

        return Command::SUCCESS;
    }
}
