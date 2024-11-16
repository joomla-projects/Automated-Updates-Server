<?php

namespace Tests\Unit\Jobs;

use App\Jobs\UpdateSite;
use App\Models\Site;
use App\RemoteSite\Connection;
use App\RemoteSite\Responses\FinalizeUpdate;
use App\RemoteSite\Responses\GetUpdate;
use App\RemoteSite\Responses\HealthCheck;
use App\RemoteSite\Responses\PrepareUpdate;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UpdateSiteTest extends TestCase
{
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

        $object = new UpdateSite($site, "1.0.1");
        $object->handle();
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
}
