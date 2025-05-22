<?php

namespace App\Network;

class DNSLookup
{
    public function getIPs(string $hostname): array
    {
        // IP as host given
        $ips = filter_var($hostname, FILTER_VALIDATE_IP) ? [$hostname] : [];

        // Hostname given, resolve IPs
        if (count($ips) === 0) {
            try {
                $dnsResults = dns_get_record($hostname, DNS_A + DNS_AAAA);
            } catch (\Throwable $e) {
                return [];
            }

            if ($dnsResults) {
                $ips = array_map(function ($dnsResult) {
                    return !empty($dnsResult['ip']) ? $dnsResult['ip'] : $dnsResult['ipv6'];
                }, $dnsResults);
            }
        }

        return $ips;
    }
}
