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
    protected int $updateLimit;
    protected string $targetVersion;

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
        $this->targetVersion = $this->queryTargetVersion();

        if (!$this->confirm("Are you sure you would like to push the updates for " . $this->targetVersion)) {
            return Command::FAILURE;
        }

        $this->output->writeln('Pushing update jobs');

        // Get update-ready sites in correct major branch that are not yet on the target version
        $sites = Site::query()
            ->where(
                'cms_version',
                'like',
                $this->targetVersion[0] . '%'
            )
            ->where(
                'update_requirement_state',
                '=',
                1
            )
            ->where(
                'cms_version',
                '!=',
                $this->targetVersion
            );

        // Query the amount of sites to be updated
        // @phpstan-ignore-next-line
        $this->updateLimit = (int) $this->ask('How many updates will be pushed? - Use 0 for "ALL"', "100");

        // Chunk and push to queue
        $sites->chunkById(
            100,
            function (Collection $chunk) {
                // Show progress
                $this->output->write('.');

                // Push each site check to queue
                $chunk->each(function (Site $site) {
                    if ($this->updateLimit > 0 && $this->totalPushed >= $this->updateLimit) {
                        return;
                    }

                    $this->totalPushed++;

                    UpdateSite::dispatch($site, $this->targetVersion)->onQueue('updates');
                });
            }
        );

        // Result
        $this->output->writeln("");
        $this->output->writeln('Pushed ' . $this->totalPushed . ' pending jobs to queue');

        return Command::SUCCESS;
    }
}
