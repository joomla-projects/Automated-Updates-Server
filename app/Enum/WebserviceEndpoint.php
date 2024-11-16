<?php

namespace App\Enum;

enum WebserviceEndpoint: string
{
    case HEALTH_CHECK = "/api/index.php/v1/joomlaupdate/healthcheck";
    case FETCH_UPDATES = "/api/index.php/v1/joomlaupdate/fetchUpdate";
    case PREPARE_UPDATE = "/api/index.php/v1/joomlaupdate/prepareUpdate";
    case FINALIZE_UPDATE = "/api/index.php/v1/joomlaupdate/finalizeUpdate";
}
