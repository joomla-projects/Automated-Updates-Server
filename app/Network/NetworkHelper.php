<?php

namespace App\Network;

class NetworkHelper
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

    public function isValidRemoteHost(string $hostname): bool
    {
        $ips = $this->getIPs($hostname);

        if (!count($ips)) {
            return false;
        }

        // Check each resolved IP
        foreach ($ips as $ip) {
            if (!$this->isValidRemoteIp($ip)) {
                return false;
            }
        }

        return true;
    }

    public function isValidRemoteIp(string $ip): bool
    {
        if (!filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE |  FILTER_FLAG_NO_RES_RANGE
        )) {
            return false;
        }

        return true;
    }
}
