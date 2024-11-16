<?php

namespace App\DTO;

abstract class BaseDTO
{
    public static function from(array $data): static
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

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
