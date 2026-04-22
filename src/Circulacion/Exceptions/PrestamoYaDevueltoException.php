<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class PrestamoYaDevueltoException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('El préstamo ya fue devuelto');
    }

    public function getErrorCode(): string
    {
        return 'PRESTAMO_YA_DEVUELTO';
    }
}
