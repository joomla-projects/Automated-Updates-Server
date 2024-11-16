<?php

namespace App\RemoteSite\Responses;

abstract class BaseResponse
{
    public static function from(array $data): self
    {
        $reflect = new \ReflectionClass(static::class);
        $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);

        $arguments = [];

        foreach ($properties as $property) {
            if (!array_key_exists($property->name, $data)) {
                continue;
            }

            $arguments[$property->name] = $data[$property->name];
        }

        return $reflect->newInstanceArgs($arguments);
    }
}
