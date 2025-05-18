<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CheckSiteHealth;
use App\Jobs\UpdateSite;
use App\Models\Site;
use App\RemoteSite\Connection;
use App\RemoteSite\Responses\HealthCheck;
use App\TUF\TufFetcher;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CheckSiteHealthTest extends TestCase
{
    public function testCheckSiteHealthUpdatesDbRow()
    {
        $healthMock = $this->getHealthCheckMock();

        $siteMock = $this->getSiteMock($healthMock);
        $siteMock->expects($this->once())
            ->method('fill')
            ->with($healthMock->toArray());

        $tufMock = $this->getMockBuilder(TufFetcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tufMock->method('getLatestVersionForBranch')->willReturn(null);

        App::bind(TufFetcher::class, fn () => $tufMock);

        $object = new CheckSiteHealth($siteMock);
        $object->handle();
    }

    public function testCheckHealthTriggersUpdateJobIfNewerVersionIsAvailable()
    {
        Queue::fake();

        $healthMock = $this->getHealthCheckMock();

        $siteMock = $this->getSiteMock($healthMock);
        $siteMock->expects($this->once())
            ->method('fill')
            ->with($healthMock->toArray());

        $tufMock = $this->getMockBuilder(TufFetcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tufMock->method('getLatestVersionForBranch')->willReturn("2.0.0");

        App::bind(TufFetcher::class, fn () => $tufMock);

        $object = new CheckSiteHealth($siteMock);
        $object->handle();

        Queue::assertPushed(UpdateSite::class);
    }

    protected function getSiteMock($healthMock)
    {
        $connectionMock = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $connectionMock
            ->method("__call")
            ->willReturnCallback(
                function () use ($healthMock) {
                    return $healthMock;
                }
            );

        $siteMock = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConnectionAttribute', 'fill', 'save'])
            ->getMock();

        $siteMock->method('getConnectionAttribute')->willReturn($connectionMock);
        $siteMock->method('save')->willReturn(true);
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
            "server_os" => "Joomla OS 1.0.0",
            "update_requirement_state" => true
        ];

        return HealthCheck::from([
            ...$defaults,
            ...$overrides
        ]);
    }

}
