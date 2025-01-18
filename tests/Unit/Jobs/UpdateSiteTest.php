<?php

namespace Tests\Unit\Jobs;

use App\Exceptions\UpdateException;
use App\Jobs\UpdateSite;
use App\Models\Site;
use App\Models\Update;
use App\RemoteSite\Connection;
use App\RemoteSite\Responses\FinalizeUpdate;
use App\RemoteSite\Responses\GetUpdate;
use App\RemoteSite\Responses\HealthCheck;
use App\RemoteSite\Responses\PrepareUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UpdateSiteTest extends TestCase
{
    use RefreshDatabase;

    public function testJobQuitsIfTargetVersionIsEqualOrNewer()
    {
        $site = $this->getSiteMock(['checkHealth' => $this->getHealthCheckMock(["cms_version" => "1.0.0"])]);

        Log::spy();

        $object = new UpdateSite($site, "1.0.0");
        $object->handle();

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Site is already up to date');
            });

        $this->assertTrue(true);
    }

    public function testJobQuitsIfNoUpdateIsAvailable()
    {
        $site = $this->getSiteMock(
            [
                'checkHealth' => $this->getHealthCheckMock(),
                'getUpdate' => $this->getGetUpdateMock(null)
            ]
        );

        Log::spy();

        $object = new UpdateSite($site, "1.0.1");
        $object->handle();

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'No update available for site');
            });

        $this->assertTrue(true);
    }

    public function testJobQuitsIfAvailabelUpdateDoesNotMatchTargetVersion()
    {
        $site = $this->getSiteMock(
            [
                'checkHealth' => $this->getHealthCheckMock(),
                'getUpdate' => $this->getGetUpdateMock("1.0.2")
            ]
        );

        Log::spy();

        $object = new UpdateSite($site, "1.0.1");
        $object->handle();

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Update version mismatch for site');
            });

        $this->assertTrue(true);
    }

    public function testJobFailsIfFinalizeUpdateReturnsFalse()
    {
        $this->expectExceptionMessage("Update for site failed in postprocessing: 1");

        $site = $this->getSiteMock(
            [
                'checkHealth' => $this->getHealthCheckMock(),
                'getUpdate' => $this->getGetUpdateMock("1.0.1"),
                'prepareUpdate' => $this->getPrepareUpdateMock(),
                'finalizeUpdate' => $this->getFinalizeUpdateMock(false)
            ]
        );

        App::bind(Connection::class, fn () => $this->getSuccessfulExtractionMock());

        $object = new UpdateSite($site, "1.0.1");
        $object->handle();
    }

    public function testJobWritesFailLogOnFailing()
    {
        $siteMock = $this->getMockBuilder(Site::class)
            ->onlyMethods(['getConnectionAttribute', 'getFrontendStatus'])
            ->getMock();

        $siteMock->id = 1;
        $siteMock->url = "http://example.org";
        $siteMock->cms_version = "1.0.0";

        $object = new UpdateSite($siteMock, "1.0.1");
        $object->failed(new UpdateException("finalize", "This is a test"));

        $failedUpdate = Update::first();

        $this->assertEquals(false, $failedUpdate->result);
        $this->assertEquals("1.0.0", $failedUpdate->old_version);
        $this->assertEquals("1.0.1", $failedUpdate->new_version);
        $this->assertEquals("finalize", $failedUpdate->failed_step);
        $this->assertEquals("This is a test", $failedUpdate->failed_message);
        $this->assertNotEmpty($failedUpdate->failed_trace);
    }

    public function testJobWritesSuccessLogForSuccessfulJobs()
    {
        $site = $this->getSiteMock(
            [
                'checkHealth' => $this->getHealthCheckMock(),
                'getUpdate' => $this->getGetUpdateMock("1.0.1"),
                'prepareUpdate' => $this->getPrepareUpdateMock(),
                'finalizeUpdate' => $this->getFinalizeUpdateMock(true)
            ]
        );

        App::bind(Connection::class, fn () => $this->getSuccessfulExtractionMock());

        $object = new UpdateSite($site, "1.0.1");
        $object->handle();

        $updateRow = Update::first();

        $this->assertEquals(true, $updateRow->result);
        $this->assertEquals("1.0.0", $updateRow->old_version);
        $this->assertEquals("1.0.1", $updateRow->new_version);
    }

    protected function getSiteMock(array $responses)
    {
        $connectionMock = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $connectionMock
            ->method("__call")
            ->willReturnCallback(
                function ($method) use ($responses) {
                    return $responses[$method];
                }
            );

        $siteMock = $this->getMockBuilder(Site::class)
            ->onlyMethods(['getConnectionAttribute', 'getFrontendStatus'])
            ->getMock();

        $siteMock->method('getConnectionAttribute')->willReturn($connectionMock);
        $siteMock->method('getFrontendStatus')->willReturn(200);
        $siteMock->id = 1;
        $siteMock->url = "http://example.org";
        $siteMock->cms_version = "1.0.0";

        return $siteMock;
    }

    protected function getHealthCheckMock($overrides = [])
    {
        $defaults = [
            "php_version" => "1.0.0",
            "db_type" => "mysqli",
            "db_version" => "1.0.0",
            "cms_version" => "1.0.0",
            "server_os" => "Joomla OS 1.0.0"
        ];

        return HealthCheck::from([
            ...$defaults,
            ...$overrides
        ]);
    }

    protected function getGetUpdateMock($version)
    {
        return GetUpdate::from([
            "availableUpdate" => $version
        ]);
    }

    protected function getFinalizeUpdateMock(bool $success)
    {
        return FinalizeUpdate::from([
            "success" => $success
        ]);
    }

    protected function getPrepareUpdateMock($overrides = [])
    {
        $defaults = [
            "password" => "foobar123",
            "filesize" => 123456
        ];

        return PrepareUpdate::from([
            ...$defaults,
            ...$overrides
        ]);
    }

    protected function getSuccessfulExtractionMock()
    {
        $connectionMock = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $connectionMock
            ->method("performExtractionRequest")
            ->willReturnCallback(
                function ($data) {
                    switch ($data["task"]) {
                        case "ping":
                            return ["message" => "Success"];

                        case "startExtract":
                            return ["done" => true];

                        case "finalizeUpdate":
                            return ["success" => true];
                    }
                }
            );

        return $connectionMock;
    }
}
