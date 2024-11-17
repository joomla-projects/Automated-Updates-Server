<?php

namespace Tests\Api\Feature;

use App\Jobs\CheckSiteHealth;
use App\Models\Site;
use App\RemoteSite\Connection;
use App\RemoteSite\Responses\HealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SiteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testRegisteringASiteWithoutUrlOrKeyFails(): void
    {
        $response = $this->postJson(
            '/api/v1/register',
        );

        $response->assertStatus(422);
    }

    public function testRegisteringASiteWithUrlAndKeyCreatesRow(): void
    {
        Queue::fake();

        $mock = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->method('__call')->willReturn(HealthCheck::from([
            "php_version" => "1.0.0",
            "db_type" => "mysqli",
            "db_version" => "1.0.0",
            "cms_version" => "1.0.0",
            "server_os" => "Joomla OS 1.0.0"
        ]));

        $this->app->bind(Connection::class, fn () => $mock);

        $response = $this->postJson(
            '/api/v1/register',
            ["url" => "https://www.joomla.org", "key" => "foobar123foobar123foobar123foobar123"]
        );

        $response->assertStatus(200);
        $row = Site::where('url', 'https://www.joomla.org')->first();

        $this->assertEquals("https://www.joomla.org", $row->url);
        $this->assertEquals("foobar123foobar123foobar123foobar123", $row->key);
        $this->assertEquals("1.0.0", $row->php_version);

        Queue::assertPushed(CheckSiteHealth::class);
    }

    public function testRegisteringASiteFailsWhenHealthCheckFails(): void
    {
        $mock = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->method('__call')->willThrowException(new \Exception());

        $this->app->bind(Connection::class, fn () => $mock);

        $response = $this->postJson(
            '/api/v1/register',
            ["url" => "https://www.joomla.org", "key" => "foobar123foobar123foobar123foobar123"]
        );

        $response->assertStatus(500);
    }

    protected function getConnectionMock(HealthCheck $response)
    {
        $mock = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->method('__call')->willReturn($response);

        return $mock;
    }
}
