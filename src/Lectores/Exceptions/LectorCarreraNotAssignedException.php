<?php

declare(strict_types=1);

namespace App\Lectores\Exceptions;

use App\Shared\Exceptions\AppException;

class LectorCarreraNotAssignedException extends AppException
{
    public function __construct()
    {
        parent::__construct('La carrera no esta asignada al lector');
    }

    public function getErrorCode(): string
    {
        return 'LECTOR_CARRERA_NOT_ASSIGNED';
    }

    public function getHttpStatus(): int
    {
        return 409;
    }

    public function getSafeMessage(): string
    {
        return $this->getMessage();
    }
}
