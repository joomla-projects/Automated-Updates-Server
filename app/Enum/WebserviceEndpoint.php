<?php

namespace App\Enum;

enum WebserviceEndpoint: string
{
    case HEALTH_CHECK = "/api/index.php/v1/updates/health";
    case FETCH_UPDATES = "/api/index.php/v1/updates/fetch";
    case PREPARE_UPDATE = "/api/index.php/v1/updates/prepare";
    case FINALIZE_UPDATE = "/api/index.php/v1/updates/finalize";
}
