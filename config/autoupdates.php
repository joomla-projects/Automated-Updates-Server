<?php

return [
    'healthcheck_interval' => env('HEALTH_CHECK_INTERVAL', 24),
    'cleanup_site_delay' => env('CLEANUP_SITE_DELAY', 7),
    'tuf_repo_cachetime' => env('TUF_REPO_CACHETIME', 5),
    'max_update_tries' => env('MAX_UPDATE_TRIES', 5),
    // Comma-separated list of outgoing IPs, published on the /bot page
    'bot_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('BOT_IPS', ''))))),
];
