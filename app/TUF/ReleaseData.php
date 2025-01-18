<?php

namespace App\TUF;

use App\DTO\BaseDTO;

class ReleaseData extends BaseDTO
{
    public function __construct(
        public readonly string $version,
        public readonly string $stability
    ) {
    }
}
