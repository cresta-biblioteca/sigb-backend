<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class EjemplarNoDisponibleException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('El ejemplar no está disponible para préstamo');
    }

    public function getErrorCode(): string
    {
        return 'EJEMPLAR_NO_DISPONIBLE';
    }
}
