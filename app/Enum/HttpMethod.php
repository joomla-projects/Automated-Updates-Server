<?php

namespace App\Enum;

enum HttpMethod
{
    case POST;
    case GET;
    case PATCH;
    case HEAD;
    case PUT;
    case DELETE;
}
