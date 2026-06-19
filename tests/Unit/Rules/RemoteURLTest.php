<?php

namespace Tests\Unit\Rules;

use App\Rules\RemoteURL;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RemoteURLTest extends TestCase
{
    #[DataProvider('urlDataProvider')]
    public function testRuleHandlesIpsAndHosts($host, $expectedResult, $expectedMessage)
    {
        $object = new RemoteURL();

        $object->validate('url', $host, function ($message) use ($expectedResult, $expectedMessage) {
            if (!$expectedResult) {
                $this->assertTrue(true);
                $this->assertSame($expectedMessage, $message);
            }
        });

        if ($expectedResult) {
            $this->assertTrue(true);
        }
    }

    public static function urlDataProvider(): array
    {
        return [
            ['https://127.0.0.1', false, 'Invalid URL: please provide a valid, resolvable Host that does not resolve to local IPs.'],
            ['https://localhost', false, 'Invalid URL: please provide a valid, resolvable Host that does not resolve to local IPs.'],
            ['https://10.0.0.1', false, 'Invalid URL: please provide a valid, resolvable Host that does not resolve to local IPs.'],
            ['https://invalid.host.tld', false,'Invalid URL: please provide a valid, resolvable Host that does not resolve to local IPs.'],
            ['https://joomla.org', true, '']
        ];
    }
}
