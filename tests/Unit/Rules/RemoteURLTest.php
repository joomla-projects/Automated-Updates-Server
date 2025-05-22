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
            ['https://127.0.0.1', false, 'Invalid URL: local address are disallowed as site URL.'],
            ['https://localhost', false, 'Invalid URL: local address are disallowed as site URL.'],
            ['https://10.0.0.1', false, 'Invalid URL: local address are disallowed as site URL.'],
            ['https://joomla.org', true, ''],
            ['https://invalid.host.tld', false,'Invalid URL: unresolvable site URL.'],
        ];
    }
}
