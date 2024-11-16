<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Site;
use App\RemoteSite\Connection;
use App\RemoteSite\Responses\PrepareUpdate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class UpdateSite implements ShouldQueue
{
    use Queueable;
    protected int $preUpdateCode;

    /**
     * Create a new job instance.
     */
    public function __construct(protected readonly Site $site, protected string $targetVersion)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var Connection $connection */
        $connection = $this->site->connection;

        // Test connection and get current version
        $healthResult = $connection->checkHealth();

        // Check the version
        if (version_compare($healthResult->cms_version, $this->targetVersion, ">=")) {
            Log::info("Site is already up to date: " . $this->site->id);

            return;
        }

        // Store pre-update response code
        $this->preUpdateCode = $this->site->getFrontendStatus();

        // Let site fetch available updates
        $updateResult = $connection->getUpdate();

        // Check if update is found and return if not
        if (is_null($updateResult->availableUpdate)) {
            Log::info("No update available for site: " . $this->site->id);

            return;
        }

        // Check the version and return if it does not match
        if ($updateResult->availableUpdate !== $this->targetVersion) {
            Log::info("Update version mismatch for site: " . $this->site->id);

            return;
        }

        $prepareResult = $connection->prepareUpdate($this->targetVersion);

        // Perform the actual extraction
        $this->performExtraction($prepareResult);

        // Run the postupdate steps
        if (!$connection->finalizeUpdate()->success) {
            throw new \Exception("Update for site failed in postprocessing: " . $this->site->id);
        }

        // Compare codes
        if ($this->site->getFrontendStatus() !== $this->preUpdateCode) {
            throw new \Exception("Status code has changed after update for site: " . $this->site->id);
        }
    }

    protected function performExtraction(PrepareUpdate $prepareResult): void
    {
        /** Create a separate connection with the extraction password **/
        $connection = App::makeWith(Connection::class, [
            "baseUrl" => $this->site->url,
            "key" => $prepareResult->password
        ]);

        // Ping server
        $pingResult = $connection->performExtractionRequest(["task" => "ping"]);

        if (empty($pingResult["message"]) || $pingResult["message"] === 'Invalid login') {
            throw new \Exception(
                "Invalid ping response for site: " . $this->site->id
            );
        }

        // Start extraction
        $stepResult = $connection->performExtractionRequest(["task" => "startExtract"]);

        // Run actual core update
        while (array_key_exists("done", $stepResult) && $stepResult["done"] !== true) {
            if ($stepResult["status"] !== true) {
                throw new \Exception(
                    "Invalid extract response for site: " . $this->site->id
                );
            }

            // Make next extraction step
            $stepResult = $connection->performExtractionRequest(
                [
                    "task" => "stepExtract",
                    "instance" => $stepResult["instance"]
                ]
            );
        }

        // Clean up restore
        $connection->performExtractionRequest(
            [
                "task" => "finalizeUpdate"
            ]
        );
    }
}
