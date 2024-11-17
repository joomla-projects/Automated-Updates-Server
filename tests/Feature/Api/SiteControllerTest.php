<?php

namespace Tests\Api\Feature;

use App\Models\Site;
use App\RemoteSite\Connection;
use App\RemoteSite\Responses\HealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
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
        App::bind(Connection::class, fn() => $this->getConnectionMock(HealthCheck::from([
            "php_version" => "1.0.0",
            "db_type" => "mysqli",
            "db_version" => "1.0.0",
            "cms_version" => "1.0.0",
            "server_os" => "Joomla OS 1.0.0"
        ])));

        $response = $this->postJson(
            '/api/v1/register',
            ["url" => "https://www.joomla.org", "key" => "foobar123foobar123foobar123foobar123"]
        );

        $response->assertStatus(200);
        $result = Site::where('url', 'https://www.joomla.org')->first();

        $this->assertEquals([

        ], $result->toArray());
    }

    protected function getConnectionMock(HealthCheck $response)
    {
        $mock = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();

        $mock->method('__call')->willReturn($response);

        return $mock;
    }
}
