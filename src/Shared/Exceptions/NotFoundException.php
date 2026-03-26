<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

class NotFoundException extends AppException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'ENTITY_NOT_FOUND';
    }

    public function getHttpStatus(): int
    {
        return 404;
    }

    public function getSafeMessage(): string
    {
        return $this->getMessage();
    }
}
