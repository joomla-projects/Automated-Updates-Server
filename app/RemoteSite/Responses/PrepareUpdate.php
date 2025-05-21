<?php

namespace App\RemoteSite\Responses;

use App\DTO\BaseDTO;

class PrepareUpdate extends BaseDTO implements ResponseInterface
{
    public function __construct(
        public string $password,
        public int $filesize,
        public array $preparationUrls = []
    ) {
    }
}
