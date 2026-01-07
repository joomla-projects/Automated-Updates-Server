<?php

namespace App\RemoteSite\Responses;

use App\DTO\BaseDTO;

class FinalizeUpdate extends BaseDTO implements ResponseInterface
{
    public function __construct(
        public bool $success,
        public ?array $errors = null,
    ) {
    }

    public function hasIgnorableError(): bool
    {
        if (is_null($this->errors)) {
            return false;
        }

        $errorString = (string) json_encode($this->errors);

        if (str_contains($errorString, 'Undefined constant') && str_contains($errorString, 'T4PATH_MEDIA')) {
            return true;
        }

        if (str_contains($errorString, 'Error on updating manifest cache') && str_contains($errorString, 'T4PATH_MEDIA')) {
            return true;
        }

        return false;
    }
}
