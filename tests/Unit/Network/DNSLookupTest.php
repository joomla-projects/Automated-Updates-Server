<?php

namespace Tests\Unit\Network;

use App\Network\DNSLookup;
use Tests\TestCase;

class DNSLookupTest extends TestCase
{
    public function testIpAsHostIsReturned()
    {
        $object = new DNSLookup();
        $this->assertSame(['127.0.0.1'], $object->getIPs('127.0.0.1'));
    }

    public function testEmptyArrayIsReturnedForInvalidHost()
    {
        $object = new DNSLookup();
        $this->assertSame([], $object->getIPs('invalid.host.with.bogus.tld'));
    }

    public function testIpsAreReturned()
    {
        $object = new DNSLookup();
        $this->assertGreaterThan(5, $object->getIPs('joomla.org'));
    }
}
