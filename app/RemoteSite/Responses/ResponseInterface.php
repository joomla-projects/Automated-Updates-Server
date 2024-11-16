<?php

namespace App\RemoteSite\Responses;

interface ResponseInterface
{
    public static function from(array $data): static;

    public function toArray(): array;
}
