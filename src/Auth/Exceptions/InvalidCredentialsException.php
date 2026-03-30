<?php

declare(strict_types=1);

namespace App\Auth\Exceptions;

use App\Shared\Exceptions\AppException;

class InvalidCredentialsException extends AppException
{
    public function __construct()
    {
        parent::__construct('Credenciales inválidas');
    }

    public function getErrorCode(): string
    {
        return 'INVALID_CREDENTIALS';
    }

    public function getHttpStatus(): int
    {
        return 401;
    }

    public function getSafeMessage(): string
    {
        return 'Credenciales inválidas';
    }
}
