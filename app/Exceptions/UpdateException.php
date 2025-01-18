<?php

namespace App\Exceptions;

class UpdateException extends \Exception
{
    public readonly string $updateStep;

    public function __construct(
        string $updateStep,
        string $message,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->updateStep = $updateStep;

        parent::__construct($message, $code, $previous);
    }

    public function getStep(): string
    {
        return $this->updateStep;
    }
}
