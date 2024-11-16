<?php

namespace App\RemoteSite\Responses;

use App\DTO\BaseDTO;

class GetUpdate extends BaseDTO implements ResponseInterface
{
    public function __construct(
        public ?string $availableUpdate
    ) {
    }
}
