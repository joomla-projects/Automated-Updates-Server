<?php

namespace Tests\Unit\Network;

use App\Network\NetworkHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NetworkHelperTest extends TestCase
{
    public function testIpAsHostIsReturned()
    {
        $object = new NetworkHelper();
        $this->assertSame(['127.0.0.1'], $object->getIPs('127.0.0.1'));
    }

    public function testEmptyArrayIsReturnedForInvalidHost()
    {
        $object = new NetworkHelper();
        $this->assertSame([], $object->getIPs('invalid.host.with.bogus.tld'));
    }

    public function testIpsAreReturned()
    {
        $object = new NetworkHelper();
        $this->assertGreaterThan(5, $object->getIPs('joomla.org'));
    }

    #[DataProvider('hostnameDataProvider')]
    public function testRemoteIpsAreValidate($ip, $result)
    {
        $object = new NetworkHelper();
        $this->assertEquals($result, $object->isValidRemoteHost($ip));
    }

    #[DataProvider('ipDataProvider')]
    public function testLocalIpsAreForbidden($ip, $result)
    {
        $object = new NetworkHelper();
        $this->assertEquals($result, $object->isValidRemoteIp($ip));
    }

    public static function hostnameDataProvider(): array
    {
        return [
            ['localhost', false],
            ['joomla.org', true]
        ];
    }

    public static function ipDataProvider(): array
    {
        return [
            ['127.0.0.1', false],
            ['10.0.0.1', false],
            ['8.8.8.8', true]
        ];
    }
}
