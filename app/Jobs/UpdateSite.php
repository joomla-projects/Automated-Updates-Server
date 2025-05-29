<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\UpdateException;
use App\Models\Site;
use App\RemoteSite\Connection;
use App\RemoteSite\Responses\PrepareUpdate;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class UpdateSite implements ShouldQueue, ShouldBeUnique
{
    use Queueable;
    protected ?int $preUpdateCode = null;
    public int $uniqueFor = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct(protected readonly Site $site, protected string $targetVersion)
    {
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->site->id;
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

        // Update is available, but let's check if the requirements are met
        if (!$healthResult->update_requirement_state) {
            throw new UpdateException(
                'verify',
                "Site does not meet requirements"
            );
        }

        // Store pre-update response code
        try {
            $this->preUpdateCode = $this->site->getFrontendStatus();
        } catch (RequestException $e) {
            // Catch request exceptions - they should not stop the process
            $this->preUpdateCode = $e->getResponse()?->getStatusCode();
        }

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
        if (!$connection->finalizeUpdate([
            "fromVersion" => $healthResult->cms_version,
            "updateFileName" => $prepareResult->filename
        ])->success) {
            throw new UpdateException(
                "finalize",
                "Update for site failed in postprocessing: " . $this->site->id
            );
        }

        // Compare codes
        try {
            $afterUpdateCode = $this->site->getFrontendStatus();
        } catch (RequestException $e) {
            // Again, do not fetch exceptions
            $afterUpdateCode = $e->getResponse()?->getStatusCode();
        }

        if ($afterUpdateCode !== $this->preUpdateCode) {
            throw new UpdateException(
                "afterUpdate",
                "Status code has changed after update for site: " . $this->site->id
            );
        }

        // Notify users
        $connection->notificationSuccess(["fromVersion" => $healthResult->cms_version]);

        // Trigger site health check to write the update version back to the db
        CheckSiteHealth::dispatch($this->site);
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
        // Failure caused by multiple jobs executed at the same time, ignore
        if ($exception instanceof MaxAttemptsExceededException) {
            return;
        }

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
