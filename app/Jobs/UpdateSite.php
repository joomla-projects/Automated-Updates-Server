<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\UpdateException;
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
        $updateCount = $this->site->getUpdateCount($this->targetVersion);

        if ($updateCount >= config('autoupdates.max_update_tries')) {
            Log::info("Update Loop detected for Site: " . $this->site->id . '; TargetVersion: ' . $this->targetVersion);

            return;
        }

        /** @var Connection $connection */
        $connection = $this->site->connection;

        // Test connection and get current version
        $healthResult = $connection->checkHealth();

        // Check the version
        if (version_compare($healthResult->cms_version, $this->targetVersion, ">=")) {
            Log::info("Site is already up to date: " . $this->site->id);

            return;
        }

        // Do not make a major version update
        $majorVersionCms = (int) $healthResult->cms_version;
        $majorTargetVersion = (int) $this->targetVersion;

        if ($majorVersionCms <> $majorTargetVersion) {
            Log::info("No major update for Site: " . $this->site->id);

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

        $prepareResult = $connection->prepareUpdate(["targetVersion" => $this->targetVersion]);

        // Perform the actual extraction
        try {
            $this->performExtraction($prepareResult);
        } catch (\Throwable $e) {
            throw new UpdateException(
                'extract',
                $e->getMessage(),
                (int) $e->getCode(),
                $e instanceof \Exception ? $e : null
            );
        }

        // Run the postupdate steps
        if (!$connection->finalizeUpdate(["fromVersion" => $healthResult->cms_version])->success) {
            throw new UpdateException(
                "finalize",
                "Update for site failed in postprocessing: " . $this->site->id
            );
        }

        // Compare codes
        if ($this->site->getFrontendStatus() !== $this->preUpdateCode) {
            throw new UpdateException(
                "afterUpdate",
                "Status code has changed after update for site: " . $this->site->id
            );
        }

        // Notify users
        $connection->notificationSuccess(["fromVersion" => $healthResult->cms_version]);
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

        if (empty($pingResult["message"]) || $pingResult["message"] !== 'Invalid login') {
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

        // Done, log successful update!
        $this->site->updates()->create([
            'old_version' => $this->site->cms_version,
            'new_version' => $this->targetVersion,
            'result' => true
        ]);
    }

    public function failed(\Exception $exception): void
    {
        /** @var Connection $connection */
        $connection = $this->site->connection;

        // Notify users
        $connection->notificationFailed(["fromVersion" => $this->site->cms_version]);

        // We log any issues during the update to the DB
        $this->site->updates()->create([
            'old_version' => $this->site->cms_version,
            'new_version' => $this->targetVersion,
            'result' => false,
            'failed_step' => $exception instanceof UpdateException ? $exception->getStep() : null,
            'failed_message' => $exception->getMessage(),
            'failed_trace' => $exception->getTraceAsString()
        ]);
    }
}
