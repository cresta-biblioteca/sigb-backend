<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

class ForbiddenException extends AppException
{
    public function __construct(string $message = 'Acceso denegado.')
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'FORBIDDEN';
    }

    public function getHttpStatus(): int
    {
        return 403;
    }

    public function getSafeMessage(): string
    {
        return $this->getMessage();
    }
}
