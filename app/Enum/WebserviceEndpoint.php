<?php

namespace App\Enum;

use App\RemoteSite\Responses\FinalizeUpdate;
use App\RemoteSite\Responses\GetUpdate;
use App\RemoteSite\Responses\HealthCheck;
use App\RemoteSite\Responses\PrepareUpdate;

enum WebserviceEndpoint: string
{
    case checkHealth = "/api/index.php/v1/joomlaupdate/healthcheck";
    case getUpdate = "/api/index.php/v1/joomlaupdate/getUpdate";
    case prepareUpdate = "/api/index.php/v1/joomlaupdate/prepareUpdate";
    case finalizeUpdate = "/api/index.php/v1/joomlaupdate/finalizeUpdate";

    public function getMethod(): HttpMethod
    {
        switch ($this->name) {
            case self::checkHealth->name:
            case self::getUpdate->name:
                return HttpMethod::GET;

            case self::prepareUpdate->name:
            case self::finalizeUpdate->name:
                return HttpMethod::POST;
        }

        throw new \ValueError("No method defined");
    }

    public function getResponseClass(): string
    {
        switch ($this->name) {
            case self::checkHealth->name:
                return HealthCheck::class;
            case self::getUpdate->name:
                return GetUpdate::class;
            case self::prepareUpdate->name:
                return PrepareUpdate::class;
            case self::finalizeUpdate->name:
                return FinalizeUpdate::class;
        }
    }

    public function getUrl(): string
    {
        return $this->value;
    }

    public static function tryFromName(string $name): ?static
    {
        $reflection = new \ReflectionEnum(static::class);

        if (!$reflection->hasCase($name)) {
            return null;
        }

        /** @var static */
        return $reflection->getCase($name)->getValue();
    }
}
