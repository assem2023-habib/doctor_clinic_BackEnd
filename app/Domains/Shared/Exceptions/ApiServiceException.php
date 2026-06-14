<?php

namespace App\Domains\Shared\Exceptions;

use App\Enums\HttpStatusEnum;

class ApiServiceException extends \RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $message = 'Error',
        private readonly HttpStatusEnum $status = HttpStatusEnum::BadRequest,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status->value, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatus(): HttpStatusEnum
    {
        return $this->status;
    }
}
