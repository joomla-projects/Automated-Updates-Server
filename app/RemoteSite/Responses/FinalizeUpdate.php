<?php

namespace App\RemoteSite\Responses;

use App\DTO\BaseDTO;

class FinalizeUpdate extends BaseDTO implements ResponseInterface
{
    public function __construct(
        public bool $success
    ) {
    }
}
