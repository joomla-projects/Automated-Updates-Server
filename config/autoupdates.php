<?php

return [
    'healthcheck_interval' => env('HEALTH_CHECK_INTERVAL', 24),
    'cleanup_site_delay' => env('CLEANUP_SITE_DELAY', 7),
    'tuf_repo_cachetime' => env('TUF_REPO_CACHETIME', 5),
    'max_update_tries' => env('MAX_UPDATE_TRIES', 5),
];
